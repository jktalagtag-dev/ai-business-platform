<?php

declare(strict_types=1);

namespace App\Application\DTOs\Employee;

final class UpdatePositionData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
    ) {}
}
