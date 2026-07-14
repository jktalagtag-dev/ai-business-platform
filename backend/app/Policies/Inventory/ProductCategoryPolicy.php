<?php

declare(strict_types=1);

namespace App\Policies\Inventory;

use App\Domain\Inventory\ProductCategory;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class ProductCategoryPolicy
{
    use AuthorizesViaTokenAbilities;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'categories.view');
    }

    public function view(Authenticatable $user, ProductCategory $category): bool
    {
        return $this->hasAbility($user, 'categories.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'categories.manage');
    }

    public function update(Authenticatable $user, ProductCategory $category): bool
    {
        return $this->hasAbility($user, 'categories.manage');
    }

    public function delete(Authenticatable $user, ProductCategory $category): bool
    {
        return $this->hasAbility($user, 'categories.manage');
    }
}
