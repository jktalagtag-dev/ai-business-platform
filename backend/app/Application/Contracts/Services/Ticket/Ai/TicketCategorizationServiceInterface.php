<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services\Ticket\Ai;

/**
 * AI preparation only — no implementation exists yet, and nothing binds
 * this interface in a ServiceProvider. When an AI module is built, it
 * implements this against subject/description text and TicketService can
 * optionally call it (behind a feature flag) to pre-fill `type` on ticket
 * creation instead of requiring the employee to pick one.
 */
interface TicketCategorizationServiceInterface
{
    /**
     * @return string|null one of the ticket types validated by
     *                     StoreTicketRequest (hardware, software, network,
     *                     account_access, printer, email, security, other),
     *                     or null if the model isn't confident enough to suggest one
     */
    public function suggestType(string $subject, string $description): ?string;
}
