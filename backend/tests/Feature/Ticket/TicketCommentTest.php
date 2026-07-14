<?php

declare(strict_types=1);

it('lets the requester add a public comment', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($requester['token'])->postJson("/api/v1/tickets/{$ticketId}/comments", [
        'body' => 'Any update on this?',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.body', 'Any update on this?');
    $response->assertJsonPath('data.attributes.is_internal', false);
});

it('requires a non-empty comment body', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($requester['token'])->postJson("/api/v1/tickets/{$ticketId}/comments", []);

    $response->assertStatus(422);
});

it('hides internal notes from the requester but shows them to the assigned technician', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $technician = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);
    $technicianEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $technician['user_id']])->id;

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');
    asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", [
        'technician_employee_id' => $technicianEmployeeId,
    ])->assertOk();

    asToken($technician['token'])->postJson("/api/v1/tickets/{$ticketId}/comments", [
        'body' => 'Suspect a failed motherboard.',
        'is_internal' => true,
    ])->assertCreated();

    $requesterView = asToken($requester['token'])->getJson("/api/v1/tickets/{$ticketId}/comments");
    $requesterView->assertOk();
    expect($requesterView->json('data'))->toHaveCount(0);

    $technicianView = asToken($technician['token'])->getJson("/api/v1/tickets/{$ticketId}/comments");
    $technicianView->assertOk();
    expect($technicianView->json('data'))->toHaveCount(1);
});
