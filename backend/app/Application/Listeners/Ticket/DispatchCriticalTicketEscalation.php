<?php

declare(strict_types=1);

namespace App\Application\Listeners\Ticket;

use App\Application\Events\Ticket\TicketCreated;
use App\Application\Jobs\Ticket\EscalationReminderJob;

/**
 * "Notify manager for Critical tickets" — fires immediately at creation,
 * independent of (and in addition to) SlaMonitoringJob's later breach
 * detection for tickets that are never resolved in time.
 */
final class DispatchCriticalTicketEscalation
{
    public function handle(TicketCreated $event): void
    {
        if (! $event->ticket->isCritical()) {
            return;
        }

        EscalationReminderJob::dispatch(
            $event->ticket->tenantId,
            $event->ticket->id,
            'A new Critical-priority ticket was created and needs prompt attention.'
        );
    }
}
