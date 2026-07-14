<?php

declare(strict_types=1);

namespace App\Application\DTOs\Ticket;

final class CreateTicketData
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $type,
        public readonly string $priority,
        public readonly string $subject,
        public readonly string $description,
    ) {}
}
