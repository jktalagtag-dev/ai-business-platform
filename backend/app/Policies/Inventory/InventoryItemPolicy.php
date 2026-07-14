<?php

declare(strict_types=1);

namespace App\Policies\Inventory;

use App\Domain\Inventory\InventoryItem;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class InventoryItemPolicy
{
    use AuthorizesViaTokenAbilities;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'inventory.view');
    }

    public function view(Authenticatable $user, InventoryItem $item): bool
    {
        return $this->hasAbility($user, 'inventory.view');
    }

    public function update(Authenticatable $user, InventoryItem $item): bool
    {
        return $this->hasAbility($user, 'inventory.manage');
    }
}
