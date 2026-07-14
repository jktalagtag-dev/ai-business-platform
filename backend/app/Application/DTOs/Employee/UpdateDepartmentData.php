<?php

declare(strict_types=1);

namespace App\Application\DTOs\Employee;

final class UpdateDepartmentData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $parentDepartmentId,
        public readonly ?string $managerEmployeeId,
    ) {}
}
