<?php

declare(strict_types=1);

namespace App\Domain\Automation;

final class WorkflowStep
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public readonly string $id,
        public readonly string $workflowId,
        public readonly int $stepOrder,
        public readonly string $type,
        public readonly array $config,
    ) {}

    public function isTrigger(): bool
    {
        return $this->type === 'trigger';
    }

    public function isCondition(): bool
    {
        return $this->type === 'condition';
    }

    public function isAction(): bool
    {
        return $this->type === 'action';
    }
}
