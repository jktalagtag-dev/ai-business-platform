<?php

declare(strict_types=1);

use App\Application\Events\Employee\EmployeeArchived;
use App\Application\Events\Employee\EmployeeCreated;
use App\Application\Events\Employee\EmployeeUpdated;
use App\Infrastructure\Persistence\Eloquent\Models\Employee\Department;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

it('fires EmployeeCreated and records an audit log entry on create', function () {
    Event::fake([EmployeeCreated::class]);
    $token = ownerSession()['token'];

    $employeeId = asToken($token)->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    Event::assertDispatched(EmployeeCreated::class, fn ($e) => $e->employee->id === $employeeId);

    $logs = asToken($token)->getJson("/api/v1/audit-logs?subject_type=employee&subject_id={$employeeId}");
    $actions = collect($logs->json('data'))->pluck('attributes.action')->all();
    expect($actions)->toContain('employee.created');
});

it('records granular audit actions for department, manager, and status changes', function () {
    Event::fake([EmployeeUpdated::class]);
    $session = ownerSession();
    $token = $session['token'];
    $department = Department::create(['tenant_id' => $session['tenant_id'], 'name' => 'Engineering']);

    $employeeId = asToken($token)->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');
    $managerId = asToken($token)->postJson('/api/v1/employees', createEmployeePayload(['email' => 'mgr@example.com']))->json('data.id');

    asToken($token)->patchJson("/api/v1/employees/{$employeeId}", createEmployeePayload([
        'department_id' => $department->id,
        'manager_employee_id' => $managerId,
        'employment_status' => 'on_leave',
    ]))->assertOk();

    Event::assertDispatched(EmployeeUpdated::class, fn ($e) => $e->employee->id === $employeeId);

    $logs = asToken($token)->getJson("/api/v1/audit-logs?subject_type=employee&subject_id={$employeeId}");
    $actions = collect($logs->json('data'))->pluck('attributes.action')->all();

    expect($actions)->toContain(
        'employee.updated',
        'employee.department_changed',
        'employee.manager_changed',
        'employee.status_changed',
    );
});

it('records a profile_updated audit action for self-service field changes', function () {
    $session = ownerSession();
    $token = $session['token'];

    $employeeId = asToken($token)->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    asToken($token)->patchJson("/api/v1/employees/{$employeeId}", createEmployeePayload([
        'bio' => 'Updated bio.',
    ]))->assertOk();

    $logs = asToken($token)->getJson("/api/v1/audit-logs?subject_type=employee&subject_id={$employeeId}");
    $actions = collect($logs->json('data'))->pluck('attributes.action')->all();

    expect($actions)->toContain('employee.profile_updated');
});

it('fires EmployeeArchived and records an audit log entry on delete', function () {
    Event::fake([EmployeeArchived::class]);
    $token = ownerSession()['token'];

    $employeeId = asToken($token)->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');
    asToken($token)->deleteJson("/api/v1/employees/{$employeeId}")->assertOk();

    Event::assertDispatched(EmployeeArchived::class, fn ($e) => $e->employee->id === $employeeId);

    $logs = asToken($token)->getJson("/api/v1/audit-logs?subject_type=employee&subject_id={$employeeId}");
    $actions = collect($logs->json('data'))->pluck('attributes.action')->all();
    expect($actions)->toContain('employee.archived');
});

it('uploads a profile picture and records an audit entry', function () {
    Storage::fake('public');
    $token = ownerSession()['token'];

    $employeeId = asToken($token)->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    $response = asToken($token)->post("/api/v1/employees/{$employeeId}/avatar", [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertOk();
    expect($response->json('data.attributes.avatar_url'))->not->toBeNull();

    $logs = asToken($token)->getJson("/api/v1/audit-logs?subject_type=employee&subject_id={$employeeId}");
    $actions = collect($logs->json('data'))->pluck('attributes.action')->all();
    expect($actions)->toContain('employee.avatar_updated');
});

it('rejects a non-image avatar upload', function () {
    Storage::fake('public');
    $token = ownerSession()['token'];

    $employeeId = asToken($token)->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    $response = asToken($token)->post("/api/v1/employees/{$employeeId}/avatar", [
        'avatar' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    $response->assertStatus(422);
});
