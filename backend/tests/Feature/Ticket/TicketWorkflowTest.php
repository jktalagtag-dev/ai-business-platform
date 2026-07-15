<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\Employee\Department;

function createOpenTicket(array $session, string $requesterToken): string
{
    return asToken($requesterToken)->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');
}

it('assigns a technician and moves status from open to assigned', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $technician = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);
    $technicianEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $technician['user_id']])->id;

    $ticketId = createOpenTicket($session, $requester['token']);

    $response = asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", [
        'technician_employee_id' => $technicianEmployeeId,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.attributes.status', 'assigned');
    $response->assertJsonPath('data.attributes.assigned_technician_id', $technicianEmployeeId);
});

it('reassigns a ticket to a different technician without resetting status', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $techA = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech-a@example.com');
    $techB = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech-b@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);
    $techAId = createEmployeeRecord($session['tenant_id'], ['user_id' => $techA['user_id']])->id;
    $techBId = createEmployeeRecord($session['tenant_id'], ['user_id' => $techB['user_id']])->id;

    $ticketId = createOpenTicket($session, $requester['token']);
    asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", ['technician_employee_id' => $techAId])->assertOk();

    asToken($session['token'])->patchJson("/api/v1/tickets/{$ticketId}/status", ['status' => 'in_progress'])->assertOk();

    $response = asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", ['technician_employee_id' => $techBId]);

    $response->assertOk();
    $response->assertJsonPath('data.attributes.assigned_technician_id', $techBId);
    $response->assertJsonPath('data.attributes.status', 'in_progress');
});

it('rejects assigning a technician from a different department than the ticket', function () {
    $session = ownerSession();
    $departmentA = Department::create(['tenant_id' => $session['tenant_id'], 'name' => 'Engineering']);
    $departmentB = Department::create(['tenant_id' => $session['tenant_id'], 'name' => 'Marketing']);
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $technician = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id'], 'department_id' => $departmentA->id]);
    $technicianEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $technician['user_id'], 'department_id' => $departmentB->id])->id;

    $ticketId = createOpenTicket($session, $requester['token']);

    $response = asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", [
        'technician_employee_id' => $technicianEmployeeId,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
    $response->assertJsonPath('error.details.0.field', 'technician_employee_id');
});

it('allows assigning a technician within the same department as the ticket', function () {
    $session = ownerSession();
    $department = Department::create(['tenant_id' => $session['tenant_id'], 'name' => 'Engineering']);
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $technician = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id'], 'department_id' => $department->id]);
    $technicianEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $technician['user_id'], 'department_id' => $department->id])->id;

    $ticketId = createOpenTicket($session, $requester['token']);

    $response = asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", [
        'technician_employee_id' => $technicianEmployeeId,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.attributes.assigned_technician_id', $technicianEmployeeId);
});

it('allows assigning any technician when the ticket has no department', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $technician = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);
    $technicianEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $technician['user_id']])->id;

    $ticketId = createOpenTicket($session, $requester['token']);

    $response = asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", [
        'technician_employee_id' => $technicianEmployeeId,
    ]);

    $response->assertOk();
});

it('rejects updateStatus with a status of closed (must use the close endpoint)', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = createOpenTicket($session, $requester['token']);

    $response = asToken($session['token'])->patchJson("/api/v1/tickets/{$ticketId}/status", ['status' => 'closed']);

    $response->assertStatus(422);
});

it('closes a ticket with resolution notes', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = createOpenTicket($session, $requester['token']);

    $response = asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/close", [
        'resolution_notes' => 'Replaced the power adapter.',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.attributes.status', 'closed');
    $response->assertJsonPath('data.attributes.resolution_notes', 'Replaced the power adapter.');
    expect($response->json('data.attributes.closed_at'))->not->toBeNull();
});

it('requires resolution notes to close a ticket', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = createOpenTicket($session, $requester['token']);

    $response = asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/close", []);

    $response->assertStatus(422);
});

it('rejects any status change on an already-closed ticket', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = createOpenTicket($session, $requester['token']);
    asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/close", ['resolution_notes' => 'Done.'])->assertOk();

    $response = asToken($session['token'])->patchJson("/api/v1/tickets/{$ticketId}/status", ['status' => 'open']);

    $response->assertStatus(400);
    $response->assertJsonPath('error.code', 'bad_request');
});

it('reopens a closed ticket, clearing resolution timestamps', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = createOpenTicket($session, $requester['token']);
    asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/close", ['resolution_notes' => 'Done.'])->assertOk();

    $response = asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/reopen", [
        'reason' => 'Issue recurred.',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.attributes.status', 'open');
    expect($response->json('data.attributes.closed_at'))->toBeNull();
    expect($response->json('data.attributes.resolved_at'))->toBeNull();
});

it('rejects reopening a ticket that is not resolved or closed', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = createOpenTicket($session, $requester['token']);

    $response = asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/reopen", []);

    $response->assertStatus(400);
});
