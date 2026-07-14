<?php

declare(strict_types=1);

namespace App\Policies\KnowledgeBase;

use App\Domain\KnowledgeBase\Document;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class DocumentPolicy
{
    use AuthorizesViaTokenAbilities;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'knowledge_base.view');
    }

    public function view(Authenticatable $user, Document $document): bool
    {
        return $this->hasAbility($user, 'knowledge_base.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'knowledge_base.manage');
    }

    public function delete(Authenticatable $user, Document $document): bool
    {
        return $this->hasAbility($user, 'knowledge_base.manage');
    }
}
