<?php

declare(strict_types=1);

namespace App\Infrastructure\Employee;

use App\Application\Contracts\Services\EmployeeCodeGeneratorInterface;
use Illuminate\Support\Facades\DB;

final class EmployeeCodeGenerator implements EmployeeCodeGeneratorInterface
{
    public function next(string $tenantId): string
    {
        $number = DB::transaction(function () use ($tenantId): int {
            DB::table('employee_id_sequences')->insertOrIgnore(['tenant_id' => $tenantId, 'next_number' => 1]);

            $current = DB::table('employee_id_sequences')
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->value('next_number');

            DB::table('employee_id_sequences')
                ->where('tenant_id', $tenantId)
                ->update(['next_number' => $current + 1]);

            return $current;
        });

        return sprintf('EMP-%06d', $number);
    }
}
