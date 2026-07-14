<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services;

interface EmployeeCodeGeneratorInterface
{
    /**
     * Atomically issues the next system-generated employee number for a
     * tenant, e.g. "EMP-000123". Never user-supplied.
     */
    public function next(string $tenantId): string;
}
