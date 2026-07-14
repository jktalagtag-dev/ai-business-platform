<?php

declare(strict_types=1);

namespace App\Application\Services\Ticket;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Application\Contracts\Services\TicketCodeGeneratorInterface;
use App\Application\DTOs\Ticket\CreateTicketData;
use App\Application\DTOs\Ticket\UpdateTicketData;
use App\Application\Events\Ticket\TicketAssigned;
use App\Application\Events\Ticket\TicketCreated;
use App\Application\Events\Ticket\TicketStatusChanged;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Shared\Exceptions\InvalidTicketStatusTransitionException;
use App\Domain\Ticket\Ticket;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class TicketService
{
    private const REOPENABLE_STATUSES = ['resolved', 'closed'];

    private const TERMINAL_STATUSES = ['closed', 'cancelled'];

    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly DepartmentRepositoryInterface $departments,
        private readonly TicketCodeGeneratorInterface $codeGenerator,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(Authenticatable $actor, array $filters = [], int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Ticket::class);

        if (Gate::forUser($actor)->denies('viewAllTickets', Ticket::class)) {
            $filters['scope'] = $this->visibilityScopeFor($actor);
        }

        if (($filters['my_tickets'] ?? false) === true) {
            $filters['my_tickets_employee_id'] = $this->employees->findByUserId($actor->getAuthIdentifier())?->id;
        }
        unset($filters['my_tickets']);

        return $this->tickets->paginate($filters, $perPage);
    }

    public function find(Authenticatable $actor, string $id): Ticket
    {
        $ticket = $this->tickets->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $ticket);

        return $ticket;
    }

    public function create(Authenticatable $actor, CreateTicketData $data): Ticket
    {
        Gate::forUser($actor)->authorize('create', Ticket::class);

        $canActOnBehalfOfOthers = Gate::forUser($actor)->allows('manageAny', Ticket::class);
        $selfEmployee = $this->employees->findByUserId($actor->getAuthIdentifier());

        if (! $canActOnBehalfOfOthers && ($selfEmployee === null || $selfEmployee->id !== $data->employeeId)) {
            throw new AuthorizationException('You may only create tickets for yourself.');
        }

        $requester = $this->employees->findById($data->employeeId) ?? throw new ModelNotFoundException;

        $ticket = $this->tickets->create([
            'ticket_number' => $this->codeGenerator->next($requester->tenantId),
            'employee_id' => $requester->id,
            'department_id' => $requester->departmentId,
            'type' => $data->type,
            'priority' => $data->priority,
            'status' => 'open',
            'subject' => $data->subject,
            'description' => $data->description,
        ]);

        $this->auditLog->record($actor, 'ticket.created', 'ticket', $ticket->id, [
            'ticket_number' => $ticket->ticketNumber,
            'type' => $ticket->type,
            'priority' => $ticket->priority,
        ]);

        TicketCreated::dispatch($ticket);

        return $ticket;
    }

    public function update(Authenticatable $actor, string $id, UpdateTicketData $data): Ticket
    {
        $existing = $this->tickets->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $existing);

        $ticket = $this->tickets->update($id, [
            'type' => $data->type,
            'priority' => $data->priority,
            'subject' => $data->subject,
            'description' => $data->description,
            'resolution_notes' => $data->resolutionNotes,
        ]);

        $this->auditLog->record($actor, 'ticket.updated', 'ticket', $ticket->id, [
            'before' => ['type' => $existing->type, 'priority' => $existing->priority, 'subject' => $existing->subject],
            'after' => ['type' => $ticket->type, 'priority' => $ticket->priority, 'subject' => $ticket->subject],
        ]);

        return $ticket;
    }

    public function assign(Authenticatable $actor, string $id, string $technicianEmployeeId): Ticket
    {
        $existing = $this->tickets->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('assign', $existing);

        $this->employees->findById($technicianEmployeeId) ?? throw new ModelNotFoundException;

        $previousTechnicianId = $existing->assignedTechnicianId;
        $newStatus = $existing->status === 'open' ? 'assigned' : $existing->status;

        $ticket = $this->tickets->update($id, [
            'assigned_technician_id' => $technicianEmployeeId,
            'status' => $newStatus,
        ]);

        $action = $previousTechnicianId === null ? 'ticket.assigned' : 'ticket.reassigned';
        $this->auditLog->record($actor, $action, 'ticket', $ticket->id, [
            'previous_technician_id' => $previousTechnicianId,
            'new_technician_id' => $technicianEmployeeId,
        ]);

        TicketAssigned::dispatch($ticket, $previousTechnicianId);

        if ($newStatus !== $existing->status) {
            TicketStatusChanged::dispatch($ticket, $existing->status);
        }

        return $ticket;
    }

    public function updateStatus(Authenticatable $actor, string $id, string $status, ?string $note = null): Ticket
    {
        $existing = $this->tickets->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $existing);

        if (in_array($existing->status, self::TERMINAL_STATUSES, true)) {
            throw new InvalidTicketStatusTransitionException($existing->status, $status);
        }

        $attributes = ['status' => $status];

        if ($status === 'resolved') {
            $attributes['resolved_at'] = now();
        }

        if ($status === 'cancelled') {
            $attributes['closed_at'] = now();
        }

        $ticket = $this->tickets->update($id, $attributes);

        $this->auditLog->record($actor, 'ticket.status_changed', 'ticket', $ticket->id, [
            'before' => $existing->status,
            'after' => $ticket->status,
            'note' => $note,
        ]);

        TicketStatusChanged::dispatch($ticket, $existing->status);

        return $ticket;
    }

    public function close(Authenticatable $actor, string $id, string $resolutionNotes): Ticket
    {
        $existing = $this->tickets->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('close', $existing);

        if (in_array($existing->status, self::TERMINAL_STATUSES, true)) {
            throw new InvalidTicketStatusTransitionException($existing->status, 'closed');
        }

        $ticket = $this->tickets->update($id, [
            'status' => 'closed',
            'resolution_notes' => $resolutionNotes,
            'resolved_at' => $existing->resolvedAt ?? now(),
            'closed_at' => now(),
        ]);

        $this->auditLog->record($actor, 'ticket.closed', 'ticket', $ticket->id, [
            'resolution_notes' => $resolutionNotes,
        ]);

        TicketStatusChanged::dispatch($ticket, $existing->status);

        return $ticket;
    }

    public function reopen(Authenticatable $actor, string $id, ?string $reason = null): Ticket
    {
        $existing = $this->tickets->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('reopen', $existing);

        if (! in_array($existing->status, self::REOPENABLE_STATUSES, true)) {
            throw new InvalidTicketStatusTransitionException($existing->status, 'open');
        }

        $ticket = $this->tickets->update($id, [
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
            'sla_breached_at' => null,
        ]);

        $this->auditLog->record($actor, 'ticket.reopened', 'ticket', $ticket->id, [
            'reason' => $reason,
        ]);

        TicketStatusChanged::dispatch($ticket, $existing->status);

        return $ticket;
    }

    /**
     * @return array{employee_id: ?string, assigned_technician_id: ?string, department_id_in: list<string>}
     */
    private function visibilityScopeFor(Authenticatable $actor): array
    {
        $self = $this->employees->findByUserId($actor->getAuthIdentifier());

        return [
            'employee_id' => $self?->id,
            'assigned_technician_id' => $self?->id,
            'department_id_in' => $self ? $this->departments->managedDepartmentIds($self->id) : [],
        ];
    }
}
