<?php

declare(strict_types=1);

namespace App\Application\Jobs\Ticket;

use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Http\Support\RequestTenantContext;
use App\Infrastructure\Notifications\Ticket\TicketAssignedNotification;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs on the 'notifications' queue, dispatched by
 * DispatchTicketAssignmentNotification when a TicketAssigned event fires.
 *
 * Queue workers process jobs outside any HTTP request, so there is no
 * ambient TenantContext the way there is mid-request — this job carries
 * $tenantId explicitly (captured by the Service at dispatch time, while a
 * real request's tenant context was still active) and sets it itself
 * before touching any tenant-scoped repository. Every ticket-related job
 * in this module follows the same pattern.
 */
final class TicketAssignmentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $tenantId,
        private readonly string $ticketId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(
        RequestTenantContext $tenantContext,
        TicketRepositoryInterface $tickets,
        EmployeeRepositoryInterface $employees,
    ): void {
        $tenantContext->setTenantId($this->tenantId);

        $ticket = $tickets->findById($this->ticketId);

        if ($ticket === null || $ticket->assignedTechnicianId === null) {
            return;
        }

        $technician = $employees->findById($ticket->assignedTechnicianId);

        if ($technician === null || $technician->userId === null) {
            return;
        }

        $user = User::find($technician->userId);
        $user?->notify(new TicketAssignedNotification($ticket));
    }
}
