<?php

declare(strict_types=1);

namespace App\Application\Jobs\Ticket;

use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Http\Support\RequestTenantContext;
use App\Infrastructure\Notifications\Ticket\TicketStatusChangedNotification;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class StatusChangeNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $tenantId,
        private readonly string $ticketId,
        private readonly string $previousStatus,
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

        if ($ticket === null) {
            return;
        }

        $requester = $employees->findById($ticket->employeeId);

        if ($requester === null || $requester->userId === null) {
            return;
        }

        $user = User::find($requester->userId);
        $user?->notify(new TicketStatusChangedNotification($ticket, $this->previousStatus));
    }
}
