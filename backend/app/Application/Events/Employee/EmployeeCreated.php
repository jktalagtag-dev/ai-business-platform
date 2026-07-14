<?php

declare(strict_types=1);

namespace App\Application\Events\Employee;

use App\Domain\Employee\Employee;
use Illuminate\Foundation\Events\Dispatchable;

final class EmployeeCreated
{
    use Dispatchable;

    public function __construct(public readonly Employee $employee) {}
}
