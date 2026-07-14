<?php

declare(strict_types=1);

namespace App\Policies\Inventory;

use App\Domain\Inventory\Supplier;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class SupplierPolicy
{
    use AuthorizesViaTokenAbilities;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'suppliers.view');
    }

    public function view(Authenticatable $user, Supplier $supplier): bool
    {
        return $this->hasAbility($user, 'suppliers.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'suppliers.manage');
    }

    public function update(Authenticatable $user, Supplier $supplier): bool
    {
        return $this->hasAbility($user, 'suppliers.manage');
    }

    public function delete(Authenticatable $user, Supplier $supplier): bool
    {
        return $this->hasAbility($user, 'suppliers.manage');
    }
}
