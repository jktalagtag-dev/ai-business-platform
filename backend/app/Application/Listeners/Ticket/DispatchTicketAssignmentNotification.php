<?php

declare(strict_types=1);

namespace App\Application\Listeners\Ticket;

use App\Application\Events\Ticket\TicketAssigned;
use App\Application\Jobs\Ticket\TicketAssignmentNotificationJob;

/**
 * Sync dispatch, async execution: this listener itself runs immediately
 * (it does no I/O beyond queueing), and the actual notification send
 * happens in TicketAssignmentNotificationJob on the 'notifications' queue.
 */
final class DispatchTicketAssignmentNotification
{
    public function handle(TicketAssigned $event): void
    {
        TicketAssignmentNotificationJob::dispatch($event->ticket->tenantId, $event->ticket->id);
    }
}
