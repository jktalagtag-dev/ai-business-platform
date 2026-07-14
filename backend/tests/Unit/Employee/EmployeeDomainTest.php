<?php

declare(strict_types=1);

use App\Domain\Employee\EmergencyContact;
use App\Domain\Employee\Employee;

function makeTestEmployee(array $overrides = []): Employee
{
    return new Employee(
        id: $overrides['id'] ?? 'emp_01',
        tenantId: $overrides['tenantId'] ?? 'tenant_01',
        userId: $overrides['userId'] ?? null,
        employeeNumber: $overrides['employeeNumber'] ?? 'EMP-000001',
        firstName: $overrides['firstName'] ?? 'Grace',
        lastName: $overrides['lastName'] ?? 'Hopper',
        email: $overrides['email'] ?? null,
        phone: $overrides['phone'] ?? null,
        departmentId: $overrides['departmentId'] ?? null,
        positionId: $overrides['positionId'] ?? null,
        managerEmployeeId: $overrides['managerEmployeeId'] ?? null,
        employmentType: $overrides['employmentType'] ?? 'full_time',
        employmentStatus: $overrides['employmentStatus'] ?? 'active',
        hireDate: $overrides['hireDate'] ?? '2024-01-15',
        terminationDate: $overrides['terminationDate'] ?? null,
        address: $overrides['address'] ?? null,
        emergencyContact: $overrides['emergencyContact'] ?? null,
        avatarPath: $overrides['avatarPath'] ?? null,
        bio: $overrides['bio'] ?? null,
    );
}

it('builds the full name from first and last name', function () {
    $employee = makeTestEmployee(['firstName' => 'Grace', 'lastName' => 'Hopper']);

    expect($employee->fullName())->toBe('Grace Hopper');
});

it('reports active only when employment_status is active', function () {
    expect(makeTestEmployee(['employmentStatus' => 'active'])->isActive())->toBeTrue();
    expect(makeTestEmployee(['employmentStatus' => 'on_leave'])->isActive())->toBeFalse();
    expect(makeTestEmployee(['employmentStatus' => 'terminated'])->isActive())->toBeFalse();
});

it('round-trips an emergency contact through toArray and fromArray', function () {
    $contact = new EmergencyContact(name: 'Mary Hopper', relationship: 'Mother', phone: '+1-555-0100', email: null);

    $array = $contact->toArray();
    $rebuilt = EmergencyContact::fromArray($array);

    expect($array)->toBe([
        'name' => 'Mary Hopper',
        'relationship' => 'Mother',
        'phone' => '+1-555-0100',
        'email' => null,
    ]);
    expect($rebuilt->name)->toBe('Mary Hopper');
    expect($rebuilt->phone)->toBe('+1-555-0100');
});
