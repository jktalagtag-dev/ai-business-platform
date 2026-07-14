<?php

declare(strict_types=1);

namespace App\Application\DTOs\Employee;

use App\Domain\Employee\EmergencyContact;

final class UpdateEmployeeData
{
    /**
     * @param  array<string, mixed>|null  $address
     */
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $departmentId,
        public readonly ?string $positionId,
        public readonly ?string $managerEmployeeId,
        public readonly string $employmentType,
        public readonly string $employmentStatus,
        public readonly string $hireDate,
        public readonly ?string $terminationDate,
        public readonly ?array $address,
        public readonly ?EmergencyContact $emergencyContact,
        public readonly ?string $bio,
    ) {}

    /**
     * Fields only an employees.manage-capable actor may change. A self-service
     * update (Employees can only update their own profile) that includes a
     * *different* value for any of these is rejected — see EmployeeService::update().
     *
     * @return array<string, mixed>
     */
    public function restrictedFields(): array
    {
        return [
            'department_id' => $this->departmentId,
            'position_id' => $this->positionId,
            'manager_employee_id' => $this->managerEmployeeId,
            'employment_type' => $this->employmentType,
            'employment_status' => $this->employmentStatus,
            'hire_date' => $this->hireDate,
            'termination_date' => $this->terminationDate,
        ];
    }
}
