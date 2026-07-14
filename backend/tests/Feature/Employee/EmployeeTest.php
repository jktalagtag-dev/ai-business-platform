<?php

declare(strict_types=1);

function createEmployeePayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'email' => 'grace@example.com',
        'hire_date' => '2024-01-15',
    ], $overrides);
}

it('creates an employee with a system-generated employee number', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/employees', createEmployeePayload());

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.first_name', 'Grace');
    expect($response->json('data.attributes.employee_number'))->toMatch('/^EMP-\d{6}$/');
});

it('ignores any client-supplied employee_number and always generates its own', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/employees', createEmployeePayload([
        'employee_number' => 'HACKED-001',
    ]));

    $response->assertCreated();
    expect($response->json('data.attributes.employee_number'))->not->toBe('HACKED-001');
});

it('generates sequential employee numbers per tenant', function () {
    $token = ownerSession()['token'];

    $first = asToken($token)->postJson('/api/v1/employees', createEmployeePayload(['email' => 'a@example.com']));
    $second = asToken($token)->postJson('/api/v1/employees', createEmployeePayload(['email' => 'b@example.com']));

    expect($first->json('data.attributes.employee_number'))->toBe('EMP-000001');
    expect($second->json('data.attributes.employee_number'))->toBe('EMP-000002');
});

it('lists, shows, updates, and archives (soft-deletes) an employee', function () {
    $token = ownerSession()['token'];

    $employeeId = asToken($token)->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    $index = asToken($token)->getJson('/api/v1/employees');
    expect($index->json('data'))->toHaveCount(1);

    $show = asToken($token)->getJson("/api/v1/employees/{$employeeId}");
    $show->assertOk();

    $update = asToken($token)->patchJson("/api/v1/employees/{$employeeId}", createEmployeePayload([
        'first_name' => 'Grace', 'last_name' => 'Murray Hopper',
    ]));
    $update->assertOk();
    $update->assertJsonPath('data.attributes.last_name', 'Murray Hopper');

    $destroy = asToken($token)->deleteJson("/api/v1/employees/{$employeeId}");
    $destroy->assertOk();

    asToken($token)->getJson("/api/v1/employees/{$employeeId}")->assertStatus(404);
});

it('rejects duplicate employee email within the same tenant', function () {
    $token = ownerSession()['token'];

    asToken($token)->postJson('/api/v1/employees', createEmployeePayload(['email' => 'dup@example.com']))->assertCreated();

    $response = asToken($token)->postJson('/api/v1/employees', createEmployeePayload(['email' => 'dup@example.com']));

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
});

it('validates foreign keys for department, position, and manager', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/employees', createEmployeePayload([
        'department_id' => 'not-a-real-id',
        'position_id' => 'not-a-real-id',
        'manager_employee_id' => 'not-a-real-id',
    ]));

    $response->assertStatus(422);
    $fields = collect($response->json('error.details'))->pluck('field')->unique()->values()->all();
    expect($fields)->toContain('department_id', 'position_id', 'manager_employee_id');
});

it('requires first_name, last_name, and hire_date', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/employees', []);

    $response->assertStatus(422);
    $fields = collect($response->json('error.details'))->pluck('field')->unique()->values()->all();
    expect($fields)->toContain('first_name', 'last_name', 'hire_date');
});

it('filters employees by department, position, and employment_status', function () {
    $token = ownerSession()['token'];
    $departmentId = asToken($token)->postJson('/api/v1/departments', ['name' => 'Engineering'])->json('data.id');
    $positionId = asToken($token)->postJson('/api/v1/positions', ['title' => 'Engineer'])->json('data.id');

    asToken($token)->postJson('/api/v1/employees', createEmployeePayload([
        'email' => 'in-dept@example.com', 'department_id' => $departmentId, 'position_id' => $positionId,
    ]))->assertCreated();
    asToken($token)->postJson('/api/v1/employees', createEmployeePayload([
        'email' => 'no-dept@example.com', 'employment_status' => 'on_leave',
    ]))->assertCreated();

    $byDepartment = asToken($token)->getJson("/api/v1/employees?department_id={$departmentId}");
    expect($byDepartment->json('data'))->toHaveCount(1);

    $byStatus = asToken($token)->getJson('/api/v1/employees?employment_status=on_leave');
    expect($byStatus->json('data'))->toHaveCount(1);
    $byStatus->assertJsonPath('data.0.attributes.email', 'no-dept@example.com');
});

it('searches employees by name, email, or employee number', function () {
    $token = ownerSession()['token'];

    asToken($token)->postJson('/api/v1/employees', createEmployeePayload(['email' => 'grace@example.com']))->assertCreated();
    asToken($token)->postJson('/api/v1/employees', createEmployeePayload([
        'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada.employee@example.com',
    ]))->assertCreated();

    $response = asToken($token)->getJson('/api/v1/employees?search=Ada');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.attributes.first_name', 'Ada');
});

it('sorts employees by the given field and direction', function () {
    $token = ownerSession()['token'];

    asToken($token)->postJson('/api/v1/employees', createEmployeePayload(['first_name' => 'Zoe', 'email' => 'z@example.com']))->assertCreated();
    asToken($token)->postJson('/api/v1/employees', createEmployeePayload(['first_name' => 'Amy', 'email' => 'a@example.com']))->assertCreated();

    $response = asToken($token)->getJson('/api/v1/employees?sort=first_name&direction=asc');

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('attributes.first_name')->all();
    expect($names)->toBe(['Amy', 'Zoe']);
});

it('paginates employees with cursor metadata', function () {
    $token = ownerSession()['token'];

    foreach (range(1, 3) as $i) {
        asToken($token)->postJson('/api/v1/employees', createEmployeePayload(['email' => "emp{$i}@example.com"]))->assertCreated();
    }

    $response = asToken($token)->getJson('/api/v1/employees?per_page=2');

    $response->assertOk();
    expect($response->json('meta.pagination'))->toHaveKeys(['next_cursor', 'prev_cursor', 'per_page']);
});

it('prevents an employee from being set as their own manager', function () {
    $token = ownerSession()['token'];

    $employeeId = asToken($token)->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    $response = asToken($token)->patchJson("/api/v1/employees/{$employeeId}", createEmployeePayload([
        'manager_employee_id' => $employeeId,
    ]));

    $response->assertStatus(422);
});

it('cannot see or modify another tenant\'s employees', function () {
    $tokenA = ownerSession(['email' => 'a@example.com', 'tenant_name' => 'Tenant A'])['token'];
    $employeeId = asToken($tokenA)->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    $tokenB = ownerSession(['email' => 'b@example.com', 'tenant_name' => 'Tenant B'])['token'];

    asToken($tokenB)->getJson("/api/v1/employees/{$employeeId}")->assertStatus(404);
});
