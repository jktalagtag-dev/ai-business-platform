<?php

declare(strict_types=1);

namespace App\Domain\Ticket;

final class TicketComment
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $ticketId,
        public readonly ?string $authorEmployeeId,
        public readonly string $body,
        public readonly bool $isInternal,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
