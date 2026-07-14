<?php

declare(strict_types=1);

namespace App\Application\Rules;

use App\Application\Contracts\Services\TenantContextInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

final class ExistsInCurrentTenant implements ValidationRule
{
    public function __construct(private readonly string $table) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = DB::table($this->table)
            ->where('tenant_id', app(TenantContextInterface::class)->tenantId())
            ->where('id', $value)
            ->exists();

        if (! $exists) {
            $fail('The selected :attribute is invalid.');
        }
    }
}
