<?php

declare(strict_types=1);

namespace App\Application\Listeners\Ticket;

use App\Application\Events\Ticket\TicketStatusChanged;
use App\Application\Jobs\Ticket\StatusChangeNotificationJob;

final class DispatchStatusChangeNotification
{
    public function handle(TicketStatusChanged $event): void
    {
        StatusChangeNotificationJob::dispatch($event->ticket->tenantId, $event->ticket->id, $event->previousStatus);
    }
}
