<?php

declare(strict_types=1);

namespace App\Policies\Ticket;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Domain\Ticket\Ticket;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class TicketPolicy
{
    use AuthorizesViaTokenAbilities;

    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly DepartmentRepositoryInterface $departments,
    ) {}

    /**
     * Anyone with the broad view permission, anyone managing a department,
     * and any plain employee (to see their own tickets) may hit the list
     * endpoint — TicketService::list() scopes the query itself per caller.
     */
    public function viewAny(Authenticatable $user): bool
    {
        return $this->viewAllTickets($user) || $this->managesAnyDepartment($user) || $this->selfEmployeeId($user) !== null;
    }

    public function viewAllTickets(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'tickets.view');
    }

    /**
     * Instance-less check for "does this actor hold tickets.manage at all"
     * — used where an ability needs checking before any Ticket instance
     * exists yet (e.g. creating on behalf of another employee), distinct
     * from assign()/close()/etc. which require a specific Ticket.
     */
    public function manageAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'tickets.manage');
    }

    public function view(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->viewAllTickets($user)
            || $this->isRequester($user, $ticket)
            || $this->isAssignedTechnician($user, $ticket)
            || $this->managesDepartment($user, $ticket->departmentId);
    }

    /**
     * Any employee may create a ticket for themselves; tickets.manage
     * additionally allows creating on behalf of another employee.
     */
    public function create(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'tickets.manage') || $this->selfEmployeeId($user) !== null;
    }

    /**
     * Technicians may update tickets assigned to them; Managers may only
     * view department tickets, not update them (per the security spec).
     */
    public function update(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->hasAbility($user, 'tickets.manage') || $this->isAssignedTechnician($user, $ticket);
    }

    public function assign(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->hasAbility($user, 'tickets.manage');
    }

    public function addComment(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->hasAbility($user, 'tickets.manage')
            || $this->isRequester($user, $ticket)
            || $this->isAssignedTechnician($user, $ticket);
    }

    /**
     * Internal notes are hidden from the requesting employee — only the
     * assigned technician or an Admin may add one.
     */
    public function addInternalNote(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->hasAbility($user, 'tickets.manage') || $this->isAssignedTechnician($user, $ticket);
    }

    public function close(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->hasAbility($user, 'tickets.manage') || $this->isAssignedTechnician($user, $ticket);
    }

    public function reopen(Authenticatable $user, Ticket $ticket): bool
    {
        return $this->hasAbility($user, 'tickets.manage')
            || $this->isAssignedTechnician($user, $ticket)
            || $this->isRequester($user, $ticket);
    }

    private function isRequester(Authenticatable $user, Ticket $ticket): bool
    {
        return $ticket->employeeId === $this->selfEmployeeId($user);
    }

    private function isAssignedTechnician(Authenticatable $user, Ticket $ticket): bool
    {
        return $ticket->assignedTechnicianId !== null && $ticket->assignedTechnicianId === $this->selfEmployeeId($user);
    }

    private function managesDepartment(Authenticatable $user, ?string $departmentId): bool
    {
        if ($departmentId === null) {
            return false;
        }

        $selfId = $this->selfEmployeeId($user);

        return $selfId !== null && $this->departments->isManagedBy($departmentId, $selfId);
    }

    private function managesAnyDepartment(Authenticatable $user): bool
    {
        $selfId = $this->selfEmployeeId($user);

        return $selfId !== null && $this->departments->managedDepartmentIds($selfId) !== [];
    }

    private function selfEmployeeId(Authenticatable $user): ?string
    {
        return $this->employees->findByUserId($user->getAuthIdentifier())?->id;
    }
}
