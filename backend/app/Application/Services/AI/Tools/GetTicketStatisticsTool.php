<?php

declare(strict_types=1);

namespace App\Application\Services\AI\Tools;

use App\Application\Contracts\Services\AI\AiToolInterface;
use App\Application\Services\Ticket\TicketStatisticsService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Delegates straight to the existing, already-authorized
 * TicketStatisticsService — the same visibility scoping a human caller of
 * GET /v1/tickets/statistics gets (full tenant view for tickets.view
 * holders, department-scoped for managers, self-scoped otherwise) applies
 * automatically here, with no Ticketing code touched or duplicated.
 */
final class GetTicketStatisticsTool implements AiToolInterface
{
    public function __construct(private readonly TicketStatisticsService $statistics) {}

    public function name(): string
    {
        return 'get_ticket_statistics';
    }

    public function description(): string
    {
        return 'Returns IT ticket dashboard statistics visible to the current user: open/closed counts, average resolution time, and breakdowns by department, priority, and technician.';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass];
    }

    public function handle(Authenticatable $actor, array $arguments): array
    {
        return $this->statistics->statistics($actor);
    }
}
