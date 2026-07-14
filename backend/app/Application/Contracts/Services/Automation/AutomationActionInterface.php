<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services\Automation;

/**
 * A single action a workflow's `action` steps can run. Implementations
 * are listed in AutomationServiceProvider's ActionRegistry binding — adding
 * a new action never requires touching WorkflowExecutionService.
 */
interface AutomationActionInterface
{
    /**
     * The name a workflow step's config.action refers to, e.g. "send_notification".
     */
    public function name(): string;

    /**
     * Executes the action. $config has already had {{placeholder}} values
     * resolved against $context (see PlaceholderResolver) before this is
     * called.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array<string, mixed> stored as this step's automation_job_steps.output
     */
    public function execute(array $config, array $context): array;
}
