<?php

declare(strict_types=1);

namespace App\Policies\Ai;

use App\Domain\Ai\AiConversation;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A conversation is private to the user who created it — like a personal
 * chat history, not gated by a permission. There is deliberately no
 * "view all conversations" ability for Owner/Admin: an AI assistant chat
 * is not tenant business data in the way tickets or employee records are.
 */
final class AiConversationPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    /**
     * Also the gate for sending a message into this conversation —
     * ConversationService::find() runs this same check before ChatService
     * ever starts streaming, so there's no separate "sendMessage" ability.
     */
    public function view(Authenticatable $user, AiConversation $conversation): bool
    {
        return $conversation->isOwnedBy($user->getAuthIdentifier());
    }

    public function delete(Authenticatable $user, AiConversation $conversation): bool
    {
        return $conversation->isOwnedBy($user->getAuthIdentifier());
    }
}
