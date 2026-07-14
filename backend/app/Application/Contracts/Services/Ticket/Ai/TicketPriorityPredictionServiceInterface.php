<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services\Ticket\Ai;

/**
 * AI preparation only — see TicketCategorizationServiceInterface for the
 * wiring intent. Would let TicketService suggest a starting priority from
 * free-text content, which the employee could accept or override.
 */
interface TicketPriorityPredictionServiceInterface
{
    /**
     * @return string|null one of low/medium/high/critical, or null if
     *                     unconfident
     */
    public function predictPriority(string $subject, string $description): ?string;
}
