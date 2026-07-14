<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Contracts\Services\Automation\AutomationActionInterface;
use RuntimeException;

/**
 * Test double — deterministically throws, used to exercise
 * WorkflowExecutionService/ExecuteWorkflowJob's failure and self-managed
 * retry path without needing a real action to fail naturally.
 */
final class AlwaysFailsAutomationAction implements AutomationActionInterface
{
    public function name(): string
    {
        return 'always_fails';
    }

    public function execute(array $config, array $context): array
    {
        throw new RuntimeException('Deliberate test failure.');
    }
}
