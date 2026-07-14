<?php

declare(strict_types=1);

namespace App\Application\Jobs\Ticket;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\Ticket;
use App\Http\Support\RequestTenantContext;
use App\Infrastructure\Notifications\Ticket\TicketEscalationNotification;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the department manager (and, if assigned, the technician) that
 * a ticket needs attention — dispatched immediately for a newly-created
 * Critical ticket ("notify manager for Critical tickets"), and by
 * SlaMonitoringJob for tickets breaching their resolution-time target.
 */
final class EscalationReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $tenantId,
        private readonly string $ticketId,
        private readonly string $reason,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(
        RequestTenantContext $tenantContext,
        TicketRepositoryInterface $tickets,
        EmployeeRepositoryInterface $employees,
        DepartmentRepositoryInterface $departments,
    ): void {
        $tenantContext->setTenantId($this->tenantId);

        $ticket = $tickets->findById($this->ticketId);

        if ($ticket === null) {
            return;
        }

        $this->notifyDepartmentManager($ticket, $departments, $employees);
        $this->notifyAssignedTechnician($ticket, $employees);
    }

    private function notifyDepartmentManager(Ticket $ticket, DepartmentRepositoryInterface $departments, EmployeeRepositoryInterface $employees): void
    {
        if ($ticket->departmentId === null) {
            return;
        }

        $department = $departments->findById($ticket->departmentId);

        if ($department === null || $department->managerEmployeeId === null) {
            return;
        }

        $manager = $employees->findById($department->managerEmployeeId);

        if ($manager === null || $manager->userId === null) {
            return;
        }

        $user = User::find($manager->userId);
        $user?->notify(new TicketEscalationNotification($ticket, $this->reason));
    }

    private function notifyAssignedTechnician(Ticket $ticket, EmployeeRepositoryInterface $employees): void
    {
        if ($ticket->assignedTechnicianId === null) {
            return;
        }

        $technician = $employees->findById($ticket->assignedTechnicianId);

        if ($technician === null || $technician->userId === null) {
            return;
        }

        $user = User::find($technician->userId);
        $user?->notify(new TicketEscalationNotification($ticket, $this->reason));
    }
}
