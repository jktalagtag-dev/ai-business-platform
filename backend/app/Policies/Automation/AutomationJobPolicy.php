<?php

declare(strict_types=1);

namespace App\Policies\Automation;

use App\Domain\Automation\AutomationJob;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class AutomationJobPolicy
{
    use AuthorizesViaTokenAbilities;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'automation.view');
    }

    public function view(Authenticatable $user, AutomationJob $job): bool
    {
        return $this->hasAbility($user, 'automation.view');
    }

    public function retry(Authenticatable $user, AutomationJob $job): bool
    {
        return $this->hasAbility($user, 'automation.manage');
    }
}
