<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\Employee\Department;

it('allows HR to create employees', function () {
    $session = ownerSession();
    $hrToken = tokenForRole($session['tenant_id'], 'HR', 'hr@example.com');

    $response = asToken($hrToken)->postJson('/api/v1/employees', createEmployeePayload());

    $response->assertCreated();
});

it('blocks a plain member from creating employees', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $response = asToken($memberToken)->postJson('/api/v1/employees', createEmployeePayload());

    $response->assertStatus(403);
    $response->assertJsonPath('error.code', 'forbidden');
});

it('blocks a plain member with no management responsibility from listing the directory', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $response = asToken($memberToken)->getJson('/api/v1/employees');

    $response->assertStatus(403);
});

it('lets an employee view and update their own profile fields', function () {
    $session = ownerSession();
    $member = tokenForRoleWithUser($session['tenant_id'], 'Member', 'member@example.com');

    $employeeId = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload([
        'user_id' => $member['user_id'],
    ]))->json('data.id');

    $me = asToken($member['token'])->getJson('/api/v1/employees/me');
    $me->assertOk();
    $me->assertJsonPath('data.id', $employeeId);

    $update = asToken($member['token'])->patchJson("/api/v1/employees/{$employeeId}", createEmployeePayload([
        'phone' => '+1-555-0100',
        'bio' => 'Loves distributed systems.',
    ]));

    $update->assertOk();
    $update->assertJsonPath('data.attributes.phone', '+1-555-0100');
});

it('rejects a self-service update that changes an employment-only field', function () {
    $session = ownerSession();
    $department = Department::create(['tenant_id' => $session['tenant_id'], 'name' => 'Engineering']);
    $member = tokenForRoleWithUser($session['tenant_id'], 'Member', 'member@example.com');

    $employeeId = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload([
        'user_id' => $member['user_id'],
    ]))->json('data.id');

    $response = asToken($member['token'])->patchJson("/api/v1/employees/{$employeeId}", createEmployeePayload([
        'department_id' => $department->id,
    ]));

    $response->assertStatus(403);
});

it('rejects viewing another employee\'s profile without permission or management responsibility', function () {
    $session = ownerSession();
    $memberA = tokenForRoleWithUser($session['tenant_id'], 'Member', 'a@example.com');
    $memberB = tokenForRoleWithUser($session['tenant_id'], 'Member', 'b@example.com');

    $employeeIdB = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload([
        'user_id' => $memberB['user_id'], 'email' => 'employee-b@example.com',
    ]))->json('data.id');

    asToken($memberA['token'])->getJson("/api/v1/employees/{$employeeIdB}")->assertStatus(403);
});

it('lets a department manager view and list only employees within their managed department', function () {
    $session = ownerSession();
    $manager = tokenForRoleWithUser($session['tenant_id'], 'Member', 'manager@example.com');

    $managerEmployeeId = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload([
        'user_id' => $manager['user_id'], 'email' => 'manager-employee@example.com',
    ]))->json('data.id');

    $department = Department::create([
        'tenant_id' => $session['tenant_id'],
        'name' => 'Engineering',
        'manager_employee_id' => $managerEmployeeId,
    ]);
    $otherDepartment = Department::create(['tenant_id' => $session['tenant_id'], 'name' => 'Sales']);

    $inDeptId = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload([
        'email' => 'in-dept@example.com', 'department_id' => $department->id,
    ]))->json('data.id');
    $outOfDeptId = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload([
        'email' => 'out-of-dept@example.com', 'department_id' => $otherDepartment->id,
    ]))->json('data.id');

    $list = asToken($manager['token'])->getJson('/api/v1/employees');
    $list->assertOk();
    $ids = collect($list->json('data'))->pluck('id')->all();
    // The manager's own employee record has no department_id set (only the
    // department's manager_employee_id points back to them), so it's
    // correctly excluded from their own department-scoped list — what
    // matters here is in-department visibility vs. out-of-department denial.
    expect($ids)->toContain($inDeptId)->not->toContain($outOfDeptId, $managerEmployeeId);

    asToken($manager['token'])->getJson("/api/v1/employees/{$inDeptId}")->assertOk();
    asToken($manager['token'])->getJson("/api/v1/employees/{$outOfDeptId}")->assertStatus(403);
});

it('blocks a plain member from deleting (archiving) any employee', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $employeeId = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    asToken($memberToken)->deleteJson("/api/v1/employees/{$employeeId}")->assertStatus(403);
});
