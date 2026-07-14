<?php

declare(strict_types=1);

namespace App\Application\Events\Ticket;

use App\Domain\Ticket\Ticket;
use Illuminate\Foundation\Events\Dispatchable;

final class TicketAssigned
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?string $previousTechnicianEmployeeId,
    ) {}
}
