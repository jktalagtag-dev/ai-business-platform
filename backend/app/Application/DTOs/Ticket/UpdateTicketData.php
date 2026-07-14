<?php

declare(strict_types=1);

namespace App\Application\DTOs\Ticket;

final class UpdateTicketData
{
    public function __construct(
        public readonly string $type,
        public readonly string $priority,
        public readonly string $subject,
        public readonly string $description,
        public readonly ?string $resolutionNotes,
    ) {}
}
