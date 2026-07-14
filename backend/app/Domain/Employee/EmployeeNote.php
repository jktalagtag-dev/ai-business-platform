<?php

declare(strict_types=1);

namespace App\Domain\Employee;

final class EmployeeNote
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $employeeId,
        public readonly ?string $authorUserId,
        public readonly string $note,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
