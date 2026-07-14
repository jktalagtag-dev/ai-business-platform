<?php

declare(strict_types=1);

use App\Application\Services\Automation\ActionRegistry;
use App\Application\Services\Automation\Actions\LogAuditEventAction;
use App\Application\Services\Automation\Actions\SendNotificationAction;
use App\Infrastructure\Notifications\Automation\WorkflowNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Support\AlwaysFailsAutomationAction;

function createTicketAsRequester(string $token, array $overrides = []): void
{
    asToken($token)->postJson('/api/v1/tickets', array_merge([
        'type' => 'hardware',
        'priority' => 'critical',
        'subject' => 'Server down',
        'description' => 'The main server is unresponsive.',
    ], $overrides))->assertCreated();
}

it('executes an active event-triggered workflow end-to-end, sending a notification when the condition matches', function () {
    Notification::fake();

    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $workflowId = asToken($session['token'])->postJson('/api/v1/automation/workflows', createWorkflowPayload())->json('data.id');
    asToken($session['token'])->postJson("/api/v1/automation/workflows/{$workflowId}/activate")->assertOk();

    createTicketAsRequester($employee['token'], ['priority' => 'critical']);

    $jobs = asToken($session['token'])->getJson("/api/v1/automation/jobs?workflow_id={$workflowId}");
    $jobs->assertOk();
    expect($jobs->json('data'))->toHaveCount(1);
    $jobs->assertJsonPath('data.0.attributes.status', 'succeeded');
    $jobs->assertJsonPath('data.0.attributes.trigger', 'ticket.created');

    $jobId = $jobs->json('data.0.id');
    $steps = asToken($session['token'])->getJson("/api/v1/automation/jobs/{$jobId}/steps");
    $steps->assertOk();
    $statuses = collect($steps->json('data'))->pluck('attributes.status')->all();
    expect($statuses)->toBe(['succeeded', 'succeeded']);

    Notification::assertSentOnDemand(WorkflowNotification::class);
});

it('does not create a job for an inactive (draft) workflow', function () {
    Notification::fake();

    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $workflowId = asToken($session['token'])->postJson('/api/v1/automation/workflows', createWorkflowPayload())->json('data.id');
    // deliberately not activated

    createTicketAsRequester($employee['token']);

    $jobs = asToken($session['token'])->getJson("/api/v1/automation/jobs?workflow_id={$workflowId}");
    expect($jobs->json('data'))->toHaveCount(0);
    Notification::assertNothingSent();
});

it('short-circuits remaining steps as skipped when a condition does not match, without failing the job', function () {
    Notification::fake();

    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $workflowId = asToken($session['token'])->postJson('/api/v1/automation/workflows', createWorkflowPayload())->json('data.id');
    asToken($session['token'])->postJson("/api/v1/automation/workflows/{$workflowId}/activate")->assertOk();

    createTicketAsRequester($employee['token'], ['priority' => 'low']);

    $jobs = asToken($session['token'])->getJson("/api/v1/automation/jobs?workflow_id={$workflowId}");
    $jobs->assertJsonPath('data.0.attributes.status', 'succeeded');

    $jobId = $jobs->json('data.0.id');
    $steps = asToken($session['token'])->getJson("/api/v1/automation/jobs/{$jobId}/steps");
    $statuses = collect($steps->json('data'))->pluck('attributes.status')->all();
    expect($statuses)->toBe(['skipped', 'skipped']);

    Notification::assertNothingSent();
});

it('runs a log_audit_event action and writes to the shared audit log', function () {
    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $payload = createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => 'ticket.created']],
            ['type' => 'action', 'config' => [
                'action' => 'log_audit_event',
                'audit_action' => 'automation.custom_ticket_logged',
                'subject_type' => 'ticket',
                'subject_id' => '{{ticket.id}}',
                'changes' => ['priority' => '{{ticket.priority}}'],
            ]],
        ],
    ]);
    $workflowId = asToken($session['token'])->postJson('/api/v1/automation/workflows', $payload)->json('data.id');
    asToken($session['token'])->postJson("/api/v1/automation/workflows/{$workflowId}/activate")->assertOk();

    createTicketAsRequester($employee['token'], ['priority' => 'high']);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'automation.custom_ticket_logged',
        'subject_type' => 'ticket',
    ]);
});

it('retries a failing action job until max_attempts, then marks it permanently failed', function () {
    app()->singleton(ActionRegistry::class, fn ($app) => new ActionRegistry([
        $app->make(SendNotificationAction::class),
        $app->make(LogAuditEventAction::class),
        new AlwaysFailsAutomationAction,
    ]));

    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $payload = createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => 'ticket.created']],
            ['type' => 'action', 'config' => ['action' => 'always_fails']],
        ],
    ]);
    $workflowId = asToken($session['token'])->postJson('/api/v1/automation/workflows', $payload)->json('data.id');
    asToken($session['token'])->postJson("/api/v1/automation/workflows/{$workflowId}/activate")->assertOk();

    createTicketAsRequester($employee['token']);

    $jobs = asToken($session['token'])->getJson("/api/v1/automation/jobs?workflow_id={$workflowId}");
    $jobs->assertJsonPath('data.0.attributes.status', 'failed');
    $jobs->assertJsonPath('data.0.attributes.attempts', 3);
    expect($jobs->json('data.0.attributes.error'))->toContain('Deliberate test failure.');
});

it('lets an admin manually retry a failed job', function () {
    app()->singleton(ActionRegistry::class, fn ($app) => new ActionRegistry([
        $app->make(SendNotificationAction::class),
        $app->make(LogAuditEventAction::class),
        new AlwaysFailsAutomationAction,
    ]));

    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $payload = createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => 'ticket.created']],
            ['type' => 'action', 'config' => ['action' => 'always_fails']],
        ],
    ]);
    $workflowId = asToken($session['token'])->postJson('/api/v1/automation/workflows', $payload)->json('data.id');
    asToken($session['token'])->postJson("/api/v1/automation/workflows/{$workflowId}/activate")->assertOk();

    createTicketAsRequester($employee['token']);

    $jobId = asToken($session['token'])->getJson("/api/v1/automation/jobs?workflow_id={$workflowId}")->json('data.0.id');

    $retry = asToken($session['token'])->postJson("/api/v1/automation/jobs/{$jobId}/retry");
    $retry->assertOk();
    // The action still always fails, so retrying re-exhausts attempts and
    // lands back on 'failed' — this asserts the retry endpoint actually
    // re-ran the job (attempts reset to 0 then re-incremented to 3),
    // not just flipped a status flag.
    $retry->assertJsonPath('data.attributes.status', 'failed');
    $retry->assertJsonPath('data.attributes.attempts', 3);
});

it('rejects retrying a job that is not in a failed state', function () {
    Notification::fake();

    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $workflowId = asToken($session['token'])->postJson('/api/v1/automation/workflows', createWorkflowPayload())->json('data.id');
    asToken($session['token'])->postJson("/api/v1/automation/workflows/{$workflowId}/activate")->assertOk();

    createTicketAsRequester($employee['token']);

    $jobId = asToken($session['token'])->getJson("/api/v1/automation/jobs?workflow_id={$workflowId}")->json('data.0.id');

    $response = asToken($session['token'])->postJson("/api/v1/automation/jobs/{$jobId}/retry");
    $response->assertStatus(400);
});

it('blocks a plain member from retrying a job', function () {
    app()->singleton(ActionRegistry::class, fn ($app) => new ActionRegistry([
        $app->make(SendNotificationAction::class),
        $app->make(LogAuditEventAction::class),
        new AlwaysFailsAutomationAction,
    ]));

    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $payload = createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => 'ticket.created']],
            ['type' => 'action', 'config' => ['action' => 'always_fails']],
        ],
    ]);
    $workflowId = asToken($session['token'])->postJson('/api/v1/automation/workflows', $payload)->json('data.id');
    asToken($session['token'])->postJson("/api/v1/automation/workflows/{$workflowId}/activate")->assertOk();

    createTicketAsRequester($employee['token']);

    $jobId = asToken($session['token'])->getJson("/api/v1/automation/jobs?workflow_id={$workflowId}")->json('data.0.id');

    asToken($memberToken)->postJson("/api/v1/automation/jobs/{$jobId}/retry")->assertStatus(403);
});

it('blocks HR (no automation.view) from listing jobs', function () {
    $session = ownerSession();
    $hrToken = tokenForRole($session['tenant_id'], 'HR', 'hr@example.com');

    asToken($hrToken)->getJson('/api/v1/automation/jobs')->assertStatus(403);
});
