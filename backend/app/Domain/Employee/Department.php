<?php

declare(strict_types=1);

namespace App\Domain\Employee;

final class Department
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly ?string $parentDepartmentId,
        public readonly ?string $managerEmployeeId,
        public readonly string $name,
        public readonly ?string $description,
    ) {}
}
