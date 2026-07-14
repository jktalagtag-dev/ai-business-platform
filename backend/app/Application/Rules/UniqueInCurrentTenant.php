<?php

declare(strict_types=1);

namespace App\Application\Rules;

use App\Application\Contracts\Services\TenantContextInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UniqueInCurrentTenant implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column,
        private readonly ?string $exceptId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = DB::table($this->table)
            ->where('tenant_id', app(TenantContextInterface::class)->tenantId())
            ->where($this->column, $value);

        if ($this->exceptId !== null) {
            $query->where('id', '!=', $this->exceptId);
        }

        if (Schema::hasColumn($this->table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($query->exists()) {
            $fail('The :attribute has already been taken.');
        }
    }
}
