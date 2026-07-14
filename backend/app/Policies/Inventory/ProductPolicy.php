<?php

declare(strict_types=1);

namespace App\Policies\Inventory;

use App\Domain\Inventory\Product;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class ProductPolicy
{
    use AuthorizesViaTokenAbilities;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'products.view');
    }

    public function view(Authenticatable $user, Product $product): bool
    {
        return $this->hasAbility($user, 'products.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'products.manage');
    }

    public function update(Authenticatable $user, Product $product): bool
    {
        return $this->hasAbility($user, 'products.manage');
    }

    public function delete(Authenticatable $user, Product $product): bool
    {
        return $this->hasAbility($user, 'products.manage');
    }
}
