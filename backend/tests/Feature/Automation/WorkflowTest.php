<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function createWorkflowPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Notify ops on critical ticket',
        'description' => 'Sends an email whenever a critical-priority ticket is created.',
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => 'ticket.created']],
            ['type' => 'condition', 'config' => ['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'critical']],
            ['type' => 'action', 'config' => [
                'action' => 'send_notification',
                'to' => 'ops@example.com',
                'subject' => 'Critical ticket: {{ticket.subject}}',
                'message' => 'Ticket {{ticket.ticket_number}} was created with priority {{ticket.priority}}.',
            ]],
        ],
    ], $overrides);
}

it('creates a workflow as draft', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/automation/workflows', createWorkflowPayload());

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.status', 'draft');
    $response->assertJsonPath('data.attributes.name', 'Notify ops on critical ticket');
});

it('rejects a workflow whose first step is not a trigger', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/automation/workflows', createWorkflowPayload([
        'steps' => [
            ['type' => 'action', 'config' => ['action' => 'send_notification', 'to' => 'a@example.com', 'subject' => 's', 'message' => 'm']],
        ],
    ]));

    $response->assertStatus(422);
});

it('rejects a workflow with an unknown event trigger', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/automation/workflows', createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => 'not.a.real.event']],
            ['type' => 'action', 'config' => ['action' => 'send_notification', 'to' => 'a@example.com', 'subject' => 's', 'message' => 'm']],
        ],
    ]));

    $response->assertStatus(422);
});

it('rejects a workflow with an invalid cron expression', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/automation/workflows', createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'schedule', 'cron' => 'not a cron']],
            ['type' => 'action', 'config' => ['action' => 'send_notification', 'to' => 'a@example.com', 'subject' => 's', 'message' => 'm']],
        ],
    ]));

    $response->assertStatus(422);
});

it('accepts a valid schedule trigger', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/automation/workflows', createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'schedule', 'cron' => '0 9 * * *']],
            ['type' => 'action', 'config' => ['action' => 'send_notification', 'to' => 'a@example.com', 'subject' => 's', 'message' => 'm']],
        ],
    ]));

    $response->assertCreated();
});

it('rejects a workflow with no action step', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/automation/workflows', createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => 'ticket.created']],
            ['type' => 'condition', 'config' => ['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'critical']],
        ],
    ]));

    $response->assertStatus(422);
});

it('rejects a workflow with an unknown action name', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/automation/workflows', createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => 'ticket.created']],
            ['type' => 'action', 'config' => ['action' => 'delete_the_database']],
        ],
    ]));

    $response->assertStatus(422);
});

it('rejects a condition step with an unknown operator', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/automation/workflows', createWorkflowPayload([
        'steps' => [
            ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => 'ticket.created']],
            ['type' => 'condition', 'config' => ['field' => 'ticket.priority', 'operator' => 'roughly_equals', 'value' => 'critical']],
            ['type' => 'action', 'config' => ['action' => 'send_notification', 'to' => 'a@example.com', 'subject' => 's', 'message' => 'm']],
        ],
    ]));

    $response->assertStatus(422);
});

it('blocks a plain member from creating a workflow', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    asToken($memberToken)->postJson('/api/v1/automation/workflows', createWorkflowPayload())->assertStatus(403);
});

it('blocks HR from viewing workflows', function () {
    $session = ownerSession();
    $hrToken = tokenForRole($session['tenant_id'], 'HR', 'hr@example.com');

    asToken($hrToken)->getJson('/api/v1/automation/workflows')->assertStatus(403);
});

it('allows an admin to create, activate, pause, and delete a workflow', function () {
    $session = ownerSession();
    $adminToken = tokenForRole($session['tenant_id'], 'Admin', 'admin@example.com');

    $workflowId = asToken($adminToken)->postJson('/api/v1/automation/workflows', createWorkflowPayload())->json('data.id');

    asToken($adminToken)->postJson("/api/v1/automation/workflows/{$workflowId}/activate")
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'active');

    asToken($adminToken)->postJson("/api/v1/automation/workflows/{$workflowId}/pause")
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'paused');

    asToken($adminToken)->deleteJson("/api/v1/automation/workflows/{$workflowId}")->assertOk();
    asToken($adminToken)->getJson("/api/v1/automation/workflows/{$workflowId}")->assertStatus(404);
});

it('lists a workflow\'s ordered steps', function () {
    $token = ownerSession()['token'];
    $workflowId = asToken($token)->postJson('/api/v1/automation/workflows', createWorkflowPayload())->json('data.id');

    $response = asToken($token)->getJson("/api/v1/automation/workflows/{$workflowId}/steps");

    $response->assertOk();
    $types = collect($response->json('data'))->pluck('attributes.step_type')->all();
    expect($types)->toBe(['trigger', 'condition', 'action']);
});
