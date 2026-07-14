<?php

declare(strict_types=1);

namespace App\Domain\Employee;

final class Position
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $title,
        public readonly ?string $description,
    ) {}
}
