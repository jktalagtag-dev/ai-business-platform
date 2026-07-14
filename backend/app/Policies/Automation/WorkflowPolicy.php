<?php

declare(strict_types=1);

namespace App\Policies\Automation;

use App\Domain\Automation\Workflow;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Workflow authoring is treated as an administrative capability (it can
 * trigger emails and write audit entries), so unlike Ticketing/Knowledge
 * Base there is no broader Member access — Owner/Admin only, gated purely
 * by ability rather than any structural ownership check.
 */
final class WorkflowPolicy
{
    use AuthorizesViaTokenAbilities;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'automation.view');
    }

    public function view(Authenticatable $user, Workflow $workflow): bool
    {
        return $this->hasAbility($user, 'automation.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'automation.manage');
    }

    public function update(Authenticatable $user, Workflow $workflow): bool
    {
        return $this->hasAbility($user, 'automation.manage');
    }

    public function delete(Authenticatable $user, Workflow $workflow): bool
    {
        return $this->hasAbility($user, 'automation.manage');
    }
}
