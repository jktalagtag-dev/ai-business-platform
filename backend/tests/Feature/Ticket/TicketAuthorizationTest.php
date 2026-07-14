<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\Employee\Department;

it('blocks a member from creating a ticket on behalf of someone else', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $other = tokenForRoleWithUser($session['tenant_id'], 'Member', 'other@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);
    $otherEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $other['user_id']])->id;

    $response = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload([
        'employee_id' => $otherEmployeeId,
    ]));

    $response->assertStatus(403);
});

it('allows an admin to create a ticket on behalf of another employee', function () {
    $session = ownerSession();
    $adminToken = tokenForRole($session['tenant_id'], 'Admin', 'admin@example.com');
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $requesterEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']])->id;

    $response = asToken($adminToken)->postJson('/api/v1/tickets', createTicketPayload([
        'employee_id' => $requesterEmployeeId,
    ]));

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.employee_id', $requesterEmployeeId);
});

it('blocks an unrelated employee from viewing someone else\'s ticket', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $bystander = tokenForRoleWithUser($session['tenant_id'], 'Member', 'bystander@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);
    createEmployeeRecord($session['tenant_id'], ['user_id' => $bystander['user_id']]);

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    asToken($bystander['token'])->getJson("/api/v1/tickets/{$ticketId}")->assertStatus(403);
});

it('blocks a plain member from assigning a technician', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $technician = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);
    $technicianEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $technician['user_id']])->id;

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($requester['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", [
        'technician_employee_id' => $technicianEmployeeId,
    ]);

    $response->assertStatus(403);
});

it('lets the assigned technician update the ticket but not view the full directory', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $technician = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);
    $technicianEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $technician['user_id']])->id;

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');
    asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", [
        'technician_employee_id' => $technicianEmployeeId,
    ])->assertOk();

    asToken($technician['token'])->patchJson("/api/v1/tickets/{$ticketId}", createTicketPayload([
        'subject' => 'Updated by technician',
    ]))->assertOk();

    // A plain technician (no tickets.view) still can't see the whole directory.
    asToken($technician['token'])->getJson('/api/v1/tickets')->assertOk()
        ->assertJsonCount(1, 'data');
});

it('lets a department manager view tickets from their department only', function () {
    $session = ownerSession();
    $manager = tokenForRoleWithUser($session['tenant_id'], 'Member', 'manager@example.com');
    $managerEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $manager['user_id']])->id;

    $department = Department::create([
        'tenant_id' => $session['tenant_id'],
        'name' => 'Engineering',
        'manager_employee_id' => $managerEmployeeId,
    ]);
    $otherDepartment = Department::create(['tenant_id' => $session['tenant_id'], 'name' => 'Sales']);

    $inDeptRequester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'in-dept@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $inDeptRequester['user_id'], 'department_id' => $department->id]);

    $outOfDeptRequester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'out-of-dept@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $outOfDeptRequester['user_id'], 'department_id' => $otherDepartment->id]);

    $inDeptTicketId = asToken($inDeptRequester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');
    $outOfDeptTicketId = asToken($outOfDeptRequester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    asToken($manager['token'])->getJson("/api/v1/tickets/{$inDeptTicketId}")->assertOk();
    asToken($manager['token'])->getJson("/api/v1/tickets/{$outOfDeptTicketId}")->assertStatus(403);
});

it('blocks a requester from adding an internal note', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($requester['token'])->postJson("/api/v1/tickets/{$ticketId}/comments", [
        'body' => 'Trying to sneak in a note',
        'is_internal' => true,
    ]);

    $response->assertStatus(403);
});
