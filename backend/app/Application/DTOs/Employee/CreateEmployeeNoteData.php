<?php

declare(strict_types=1);

namespace App\Application\DTOs\Employee;

final class CreateEmployeeNoteData
{
    public function __construct(
        public readonly string $note,
    ) {}
}
