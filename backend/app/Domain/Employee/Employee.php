<?php

declare(strict_types=1);

namespace App\Domain\Employee;

final class Employee
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly ?string $userId,
        public readonly string $employeeNumber,
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
        public readonly ?string $avatarPath,
        public readonly ?string $bio,
    ) {}

    public function fullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    public function isActive(): bool
    {
        return $this->employmentStatus === 'active';
    }
}
