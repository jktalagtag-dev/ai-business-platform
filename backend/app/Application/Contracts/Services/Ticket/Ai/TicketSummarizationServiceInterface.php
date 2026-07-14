<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services\Ticket\Ai;

use App\Domain\Ticket\Ticket;

/**
 * AI preparation only. Would generate a short summary of a ticket's full
 * comment thread — useful when a technician takes over a long-running
 * ticket, or for the "Ticket History" view. Takes the comment bodies as
 * plain strings (already fetched by the caller via
 * TicketCommentRepositoryInterface) rather than depending on that
 * repository itself, keeping this contract framework- and
 * persistence-agnostic.
 */
interface TicketSummarizationServiceInterface
{
    /**
     * @param  list<string>  $commentBodies  chronological, oldest first
     */
    public function summarize(Ticket $ticket, array $commentBodies): string;
}
