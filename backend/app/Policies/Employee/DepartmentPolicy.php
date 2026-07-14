<?php

declare(strict_types=1);

namespace App\Policies\Employee;

use App\Domain\Employee\Department;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class DepartmentPolicy
{
    use AuthorizesViaTokenAbilities;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'departments.view');
    }

    public function view(Authenticatable $user, Department $department): bool
    {
        return $this->hasAbility($user, 'departments.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'departments.manage');
    }

    public function update(Authenticatable $user, Department $department): bool
    {
        return $this->hasAbility($user, 'departments.manage');
    }

    public function delete(Authenticatable $user, Department $department): bool
    {
        return $this->hasAbility($user, 'departments.manage');
    }
}
