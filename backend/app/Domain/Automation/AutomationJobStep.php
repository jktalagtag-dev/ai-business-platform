<?php

declare(strict_types=1);

namespace App\Domain\Automation;

final class AutomationJobStep
{
    /**
     * @param  array<string, mixed>|null  $output
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $automationJobId,
        public readonly ?string $workflowStepId,
        public readonly int $stepOrder,
        public readonly string $type,
        public readonly string $status,
        public readonly ?array $output,
        public readonly ?string $error,
        public readonly ?\DateTimeImmutable $startedAt,
        public readonly ?\DateTimeImmutable $finishedAt,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
