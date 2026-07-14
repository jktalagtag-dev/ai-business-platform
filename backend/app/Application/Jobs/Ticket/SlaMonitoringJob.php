<?php

declare(strict_types=1);

namespace App\Application\Jobs\Ticket;

use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\SlaPolicy;
use App\Http\Support\RequestTenantContext;
use App\Infrastructure\Persistence\Eloquent\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled (see routes/console.php), not event-driven — there is no
 * single ticket or tenant that triggers this, so unlike the other three
 * jobs it iterates every tenant itself rather than being dispatched with
 * one already in mind. For each tenant it scans open tickets and, for any
 * breaching its SlaPolicy resolution-time target that hasn't already been
 * escalated (tickets.sla_breached_at), marks it and dispatches
 * EscalationReminderJob — a one-time escalation per breach, not one every
 * scheduler tick.
 */
final class SlaMonitoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(RequestTenantContext $tenantContext, TicketRepositoryInterface $tickets, SlaPolicy $slaPolicy): void
    {
        $now = new \DateTimeImmutable;

        foreach (Tenant::pluck('id') as $tenantId) {
            $tenantContext->setTenantId($tenantId);

            foreach ($tickets->findOpenTickets() as $ticket) {
                if ($ticket->slaBreachedAt !== null) {
                    continue;
                }

                if (! $slaPolicy->isBreached($ticket->priority, $ticket->createdAt, $now)) {
                    continue;
                }

                $tickets->update($ticket->id, ['sla_breached_at' => $now]);

                EscalationReminderJob::dispatch(
                    $tenantId,
                    $ticket->id,
                    "SLA resolution target for a {$ticket->priority}-priority ticket has been exceeded."
                );
            }
        }
    }
}
