<?php

declare(strict_types=1);

namespace App\Application\Services\Ticket;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\Ticket;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class TicketStatisticsService
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly DepartmentRepositoryInterface $departments,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{open_count: int, closed_count: int, average_resolution_minutes: ?float, by_department: array<string, int>, by_priority: array<string, int>, by_technician: array<string, int>}
     */
    public function statistics(Authenticatable $actor, array $filters = []): array
    {
        Gate::forUser($actor)->authorize('viewAny', Ticket::class);

        if (Gate::forUser($actor)->denies('viewAllTickets', Ticket::class)) {
            $self = $this->employees->findByUserId($actor->getAuthIdentifier());

            $filters['scope'] = [
                'employee_id' => $self?->id,
                'assigned_technician_id' => $self?->id,
                'department_id_in' => $self ? $this->departments->managedDepartmentIds($self->id) : [],
            ];
        }

        return $this->tickets->statistics($filters);
    }
}
