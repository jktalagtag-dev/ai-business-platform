<?php

declare(strict_types=1);

namespace App\Application\DTOs\Employee;

use App\Domain\Employee\EmergencyContact;

final class CreateEmployeeData
{
    /**
     * @param  array<string, mixed>|null  $address
     */
    public function __construct(
        public readonly ?string $userId,
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
        public readonly ?array $address,
        public readonly ?EmergencyContact $emergencyContact,
        public readonly ?string $bio,
    ) {}
}
