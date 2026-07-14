<?php

declare(strict_types=1);

namespace App\Application\Events\Employee;

use App\Domain\Employee\Employee;
use Illuminate\Foundation\Events\Dispatchable;

final class EmployeeUpdated
{
    use Dispatchable;

    /**
     * @param  array<string, array{before: mixed, after: mixed}>  $changes
     */
    public function __construct(
        public readonly Employee $employee,
        public readonly array $changes,
    ) {}
}
