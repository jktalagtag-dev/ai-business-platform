<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Ticket;

use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Ticket\Ticket as TicketEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Ticket\Ticket;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class TicketRepository implements TicketRepositoryInterface
{
    private const SORTABLE_COLUMNS = ['created_at', 'priority', 'status', 'ticket_number'];

    private const OPEN_STATUSES = ['open', 'assigned', 'in_progress', 'waiting_for_user'];

    private const CLOSED_STATUSES = ['resolved', 'closed', 'cancelled'];

    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator
    {
        $sort = in_array($filters['sort'] ?? 'created_at', self::SORTABLE_COLUMNS, true)
            ? $filters['sort'] ?? 'created_at'
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query = $this->applyFilters($this->scoped(), $filters)
            ->orderBy($sort, $direction)
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($query, fn (Ticket $t): TicketEntity => $this->toDomain($t));
    }

    public function findById(string $id): ?TicketEntity
    {
        $ticket = $this->scoped()->find($id);

        return $ticket ? $this->toDomain($ticket) : null;
    }

    public function create(array $attributes): TicketEntity
    {
        $ticket = Ticket::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($ticket);
    }

    public function update(string $id, array $attributes): TicketEntity
    {
        $ticket = $this->scoped()->findOrFail($id);
        $ticket->fill($attributes)->save();

        return $this->toDomain($ticket);
    }

    public function statistics(array $filters = []): array
    {
        $base = $this->applyFilters($this->scoped(), $filters);

        $openCount = (clone $base)->whereIn('status', self::OPEN_STATUSES)->count();
        $closedCount = (clone $base)->whereIn('status', self::CLOSED_STATUSES)->count();

        // Computed in PHP rather than via SQL date-arithmetic functions
        // (e.g. Postgres's EXTRACT(EPOCH FROM ...)), which aren't portable
        // to SQLite — used by this test suite — without dialect-specific
        // branching for what is, at realistic ticket volumes, a cheap pass
        // over a small already-filtered set.
        $resolutionMinutes = (clone $base)
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at'])
            ->map(fn (Ticket $t) => ($t->resolved_at->getTimestamp() - $t->created_at->getTimestamp()) / 60);

        $averageResolutionMinutes = $resolutionMinutes->isNotEmpty() ? $resolutionMinutes->average() : null;

        $byDepartment = (clone $base)
            ->selectRaw('department_id, COUNT(*) as aggregate')
            ->groupBy('department_id')
            ->pluck('aggregate', 'department_id')
            ->mapWithKeys(fn ($count, $key) => [(string) ($key ?? 'unassigned') => (int) $count])
            ->all();

        $byPriority = (clone $base)
            ->selectRaw('priority, COUNT(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority')
            ->map(fn ($count) => (int) $count)
            ->all();

        $byTechnician = (clone $base)
            ->selectRaw('assigned_technician_id, COUNT(*) as aggregate')
            ->groupBy('assigned_technician_id')
            ->pluck('aggregate', 'assigned_technician_id')
            ->mapWithKeys(fn ($count, $key) => [(string) ($key ?? 'unassigned') => (int) $count])
            ->all();

        return [
            'open_count' => $openCount,
            'closed_count' => $closedCount,
            'average_resolution_minutes' => $averageResolutionMinutes !== null ? round((float) $averageResolutionMinutes, 1) : null,
            'by_department' => $byDepartment,
            'by_priority' => $byPriority,
            'by_technician' => $byTechnician,
        ];
    }

    public function findOpenTickets(): array
    {
        return $this->scoped()
            ->whereIn('status', self::OPEN_STATUSES)
            ->get()
            ->map(fn (Ticket $t): TicketEntity => $this->toDomain($t))
            ->all();
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(isset($filters['employee_id']), fn (Builder $q) => $q->where('employee_id', $filters['employee_id']))
            ->when(isset($filters['ticket_number']), fn (Builder $q) => $q->where('ticket_number', $filters['ticket_number']))
            ->when(isset($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn (Builder $q) => $q->where('priority', $filters['priority']))
            ->when(isset($filters['department_id']), fn (Builder $q) => $q->where('department_id', $filters['department_id']))
            ->when(
                isset($filters['assigned_technician_id']),
                fn (Builder $q) => $q->where('assigned_technician_id', $filters['assigned_technician_id'])
            )
            ->when($filters['unassigned'] ?? false, fn (Builder $q) => $q->whereNull('assigned_technician_id'))
            ->when(
                isset($filters['my_tickets_employee_id']),
                fn (Builder $q) => $q->where(fn (Builder $q) => $q
                    ->where('employee_id', $filters['my_tickets_employee_id'])
                    ->orWhere('assigned_technician_id', $filters['my_tickets_employee_id']))
            )
            ->when(isset($filters['date_from']), fn (Builder $q) => $q->where('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $q) => $q->where('created_at', '<=', $filters['date_to']))
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(fn (Builder $q) => $q->where('subject', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('ticket_number', 'like', $term));
            })
            ->when(isset($filters['scope']), function (Builder $q) use ($filters) {
                $scope = $filters['scope'];
                $q->where(function (Builder $q) use ($scope) {
                    $q->where('employee_id', $scope['employee_id'])
                        ->orWhere('assigned_technician_id', $scope['employee_id']);

                    if ($scope['department_id_in'] !== []) {
                        $q->orWhereIn('department_id', $scope['department_id_in']);
                    }
                });
            });
    }

    private function scoped(): Builder
    {
        return Ticket::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(Ticket $ticket): TicketEntity
    {
        return new TicketEntity(
            id: $ticket->id,
            tenantId: $ticket->tenant_id,
            ticketNumber: $ticket->ticket_number,
            employeeId: $ticket->employee_id,
            assignedTechnicianId: $ticket->assigned_technician_id,
            departmentId: $ticket->department_id,
            type: $ticket->type,
            priority: $ticket->priority,
            status: $ticket->status,
            subject: $ticket->subject,
            description: $ticket->description,
            resolutionNotes: $ticket->resolution_notes,
            resolvedAt: $ticket->resolved_at ? \DateTimeImmutable::createFromInterface($ticket->resolved_at) : null,
            closedAt: $ticket->closed_at ? \DateTimeImmutable::createFromInterface($ticket->closed_at) : null,
            slaBreachedAt: $ticket->sla_breached_at ? \DateTimeImmutable::createFromInterface($ticket->sla_breached_at) : null,
            createdAt: \DateTimeImmutable::createFromInterface($ticket->created_at),
        );
    }
}
