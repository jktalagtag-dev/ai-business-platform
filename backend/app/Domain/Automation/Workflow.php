<?php

declare(strict_types=1);

namespace App\Domain\Automation;

final class Workflow
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $createdByUserId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $status,
        public readonly ?\DateTimeImmutable $lastTriggeredAt,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
