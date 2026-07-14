<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Ticket;

use App\Domain\Ticket\Ticket;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface TicketRepositoryInterface
{
    /**
     * @param  array{
     *     employee_id?: string, ticket_number?: string, status?: string, priority?: string,
     *     department_id?: string, assigned_technician_id?: string, search?: string,
     *     date_from?: string, date_to?: string, unassigned?: bool,
     *     my_tickets_employee_id?: string,
     *     scope?: array{employee_id: ?string, assigned_technician_id: ?string, department_id_in: list<string>},
     *     sort?: string, direction?: string
     * }  $filters
     */
    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?Ticket;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Ticket;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $id, array $attributes): Ticket;

    /**
     * Raw aggregate rows for the dashboard — grouped counts and average
     * resolution time within the same visibility scope as paginate().
     *
     * @param  array{scope?: array{employee_id: ?string, assigned_technician_id: ?string, department_id_in: list<string>}}  $filters
     * @return array{
     *     open_count: int, closed_count: int, average_resolution_minutes: ?float,
     *     by_department: array<string, int>, by_priority: array<string, int>, by_technician: array<string, int>
     * }
     */
    public function statistics(array $filters = []): array;

    /**
     * Open/assigned/in-progress/waiting tickets, for SLA Monitoring to scan.
     *
     * @return list<Ticket>
     */
    public function findOpenTickets(): array;
}
