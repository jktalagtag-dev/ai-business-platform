<?php

declare(strict_types=1);

it('returns dashboard statistics with open/closed counts and breakdowns', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload(['priority' => 'high']))->assertCreated();
    $closedId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload(['priority' => 'low']))->json('data.id');
    asToken($session['token'])->postJson("/api/v1/tickets/{$closedId}/close", ['resolution_notes' => 'Fixed.'])->assertOk();

    $response = asToken($session['token'])->getJson('/api/v1/tickets/statistics');

    $response->assertOk();
    $response->assertJsonPath('data.open_count', 1);
    $response->assertJsonPath('data.closed_count', 1);
    $response->assertJsonPath('data.by_priority.high', 1);
    $response->assertJsonPath('data.by_priority.low', 1);
    // Not asserted against a real elapsed value — SlaMonitoringJob and the
    // resolution-time math are covered separately by SlaPolicy unit tests.
    expect($response->json('data.average_resolution_minutes'))->not->toBeNull();
});

it('blocks a plain member with no linked employee record and no management responsibility from viewing statistics', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $response = asToken($memberToken)->getJson('/api/v1/tickets/statistics');

    $response->assertStatus(403);
});
