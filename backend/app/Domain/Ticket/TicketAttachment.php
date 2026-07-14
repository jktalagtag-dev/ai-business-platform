<?php

declare(strict_types=1);

namespace App\Domain\Ticket;

final class TicketAttachment
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $ticketId,
        public readonly ?string $uploadedByEmployeeId,
        public readonly string $filePath,
        public readonly string $originalFilename,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
