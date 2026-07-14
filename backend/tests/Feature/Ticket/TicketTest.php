<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function createTicketPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'hardware',
        'priority' => 'medium',
        'subject' => 'Laptop will not power on',
        'description' => 'Pressed the power button several times, no response and no LED activity.',
    ], $overrides);
}

it('creates a ticket with a system-generated ticket number', function () {
    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $response = asToken($employee['token'])->postJson('/api/v1/tickets', createTicketPayload());

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.status', 'open');
    $response->assertJsonPath('data.attributes.ticket_type', 'hardware');
    expect($response->json('data.attributes.ticket_number'))->toMatch('/^TCK-\d{6}$/');
});

it('ignores any client-supplied status and always creates as open', function () {
    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $response = asToken($employee['token'])->postJson('/api/v1/tickets', createTicketPayload(['status' => 'closed']));

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.status', 'open');
});

it('shows a single ticket to its requester', function () {
    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $ticketId = asToken($employee['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($employee['token'])->getJson("/api/v1/tickets/{$ticketId}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $ticketId);
});

it('updates ticket content as an actor with manage rights', function () {
    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $ticketId = asToken($employee['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($session['token'])->patchJson("/api/v1/tickets/{$ticketId}", createTicketPayload([
        'subject' => 'Laptop will not power on even when plugged in',
    ]));

    $response->assertOk();
    $response->assertJsonPath('data.attributes.subject', 'Laptop will not power on even when plugged in');
});

it('blocks the requester (a plain, unassigned employee) from updating their own ticket content', function () {
    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    $ticketId = asToken($employee['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($employee['token'])->patchJson("/api/v1/tickets/{$ticketId}", createTicketPayload([
        'subject' => 'Attempted self-edit',
    ]));

    $response->assertStatus(403);
});

it('lists tickets for the owner across the whole tenant', function () {
    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    asToken($employee['token'])->postJson('/api/v1/tickets', createTicketPayload())->assertCreated();
    asToken($employee['token'])->postJson('/api/v1/tickets', createTicketPayload(['subject' => 'Printer jam']))->assertCreated();

    $response = asToken($session['token'])->getJson('/api/v1/tickets');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('filters the list by status via quick_filter', function () {
    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    asToken($employee['token'])->postJson('/api/v1/tickets', createTicketPayload(['priority' => 'critical']))->assertCreated();
    asToken($employee['token'])->postJson('/api/v1/tickets', createTicketPayload(['priority' => 'low']))->assertCreated();

    $response = asToken($session['token'])->getJson('/api/v1/tickets?quick_filter=critical');

    $response->assertOk();
    $priorities = collect($response->json('data'))->pluck('attributes.priority')->all();
    expect($priorities)->toEqual(['critical']);
});
