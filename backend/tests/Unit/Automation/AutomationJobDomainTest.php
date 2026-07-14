<?php

declare(strict_types=1);

use App\Domain\Automation\AutomationJob;

function makeAutomationJob(array $overrides = []): AutomationJob
{
    return new AutomationJob(
        id: $overrides['id'] ?? 'job_01',
        tenantId: $overrides['tenantId'] ?? 'tenant_01',
        workflowId: $overrides['workflowId'] ?? 'wf_01',
        trigger: $overrides['trigger'] ?? 'ticket.created',
        status: $overrides['status'] ?? 'queued',
        attempts: $overrides['attempts'] ?? 0,
        maxAttempts: $overrides['maxAttempts'] ?? 3,
        context: $overrides['context'] ?? null,
        error: $overrides['error'] ?? null,
        scheduledAt: $overrides['scheduledAt'] ?? null,
        startedAt: $overrides['startedAt'] ?? null,
        finishedAt: $overrides['finishedAt'] ?? null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('has attempts remaining while attempts is below max_attempts', function () {
    expect(makeAutomationJob(['attempts' => 1, 'maxAttempts' => 3])->hasAttemptsRemaining())->toBeTrue();
    expect(makeAutomationJob(['attempts' => 3, 'maxAttempts' => 3])->hasAttemptsRemaining())->toBeFalse();
});

it('is retryable only when status is failed', function () {
    expect(makeAutomationJob(['status' => 'failed'])->isRetryable())->toBeTrue();
    expect(makeAutomationJob(['status' => 'succeeded'])->isRetryable())->toBeFalse();
    expect(makeAutomationJob(['status' => 'queued'])->isRetryable())->toBeFalse();
});
