<?php

declare(strict_types=1);

namespace App\Domain\Automation;

final class AutomationJob
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $workflowId,
        public readonly string $trigger,
        public readonly string $status,
        public readonly int $attempts,
        public readonly int $maxAttempts,
        public readonly ?array $context,
        public readonly ?string $error,
        public readonly ?\DateTimeImmutable $scheduledAt,
        public readonly ?\DateTimeImmutable $startedAt,
        public readonly ?\DateTimeImmutable $finishedAt,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {}

    public function hasAttemptsRemaining(): bool
    {
        return $this->attempts < $this->maxAttempts;
    }

    public function isRetryable(): bool
    {
        return $this->status === 'failed';
    }
}
