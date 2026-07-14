<?php

declare(strict_types=1);

namespace App\Policies\Employee;

use App\Domain\Employee\Position;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class PositionPolicy
{
    use AuthorizesViaTokenAbilities;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'positions.view');
    }

    public function view(Authenticatable $user, Position $position): bool
    {
        return $this->hasAbility($user, 'positions.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'positions.manage');
    }

    public function update(Authenticatable $user, Position $position): bool
    {
        return $this->hasAbility($user, 'positions.manage');
    }

    public function delete(Authenticatable $user, Position $position): bool
    {
        return $this->hasAbility($user, 'positions.manage');
    }
}
