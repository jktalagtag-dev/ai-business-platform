<?php

declare(strict_types=1);

namespace App\Domain\Ticket;

final class Ticket
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $ticketNumber,
        public readonly string $employeeId,
        public readonly ?string $assignedTechnicianId,
        public readonly ?string $departmentId,
        public readonly string $type,
        public readonly string $priority,
        public readonly string $status,
        public readonly string $subject,
        public readonly string $description,
        public readonly ?string $resolutionNotes,
        public readonly ?\DateTimeImmutable $resolvedAt,
        public readonly ?\DateTimeImmutable $closedAt,
        public readonly ?\DateTimeImmutable $slaBreachedAt,
        public readonly \DateTimeImmutable $createdAt,
    ) {}

    public function isAssigned(): bool
    {
        return $this->assignedTechnicianId !== null;
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['resolved', 'closed', 'cancelled'], true);
    }

    public function isCritical(): bool
    {
        return $this->priority === 'critical';
    }
}
