<?php

declare(strict_types=1);

use App\Application\DTOs\Employee\UpdateEmployeeData;

it('exposes exactly the employment fields an employees.manage-capable actor may change', function () {
    $data = new UpdateEmployeeData(
        firstName: 'Grace',
        lastName: 'Hopper',
        email: null,
        phone: null,
        departmentId: 'dept_1',
        positionId: 'pos_1',
        managerEmployeeId: 'mgr_1',
        employmentType: 'full_time',
        employmentStatus: 'active',
        hireDate: '2024-01-15',
        terminationDate: null,
        address: null,
        emergencyContact: null,
        bio: null,
    );

    expect($data->restrictedFields())->toBe([
        'department_id' => 'dept_1',
        'position_id' => 'pos_1',
        'manager_employee_id' => 'mgr_1',
        'employment_type' => 'full_time',
        'employment_status' => 'active',
        'hire_date' => '2024-01-15',
        'termination_date' => null,
    ]);

    // first_name/last_name/phone/email/address/emergency_contact/bio are
    // deliberately absent — those are the self-service profile fields.
    expect(array_keys($data->restrictedFields()))->not->toContain('first_name', 'bio', 'phone', 'email');
});
