<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services\Ticket\Ai;

use App\Domain\Ticket\Ticket;

/**
 * AI preparation only. Would surface likely-relevant past resolutions
 * (e.g. via similarity search over resolved tickets' resolution_notes) to
 * a technician working an open ticket — a "suggested solutions" panel in
 * the UI, not an automatic action.
 */
interface TicketSolutionSuggestionServiceInterface
{
    /**
     * @return list<array{ticket_id: string, ticket_number: string, snippet: string, score: float}>
     */
    public function suggestSolutions(Ticket $ticket): array;
}
