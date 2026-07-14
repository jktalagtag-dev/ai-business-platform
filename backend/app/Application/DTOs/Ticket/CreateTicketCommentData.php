<?php

declare(strict_types=1);

namespace App\Application\DTOs\Ticket;

final class CreateTicketCommentData
{
    public function __construct(
        public readonly string $body,
        public readonly bool $isInternal,
    ) {}
}
