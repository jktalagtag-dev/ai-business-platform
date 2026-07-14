<?php

declare(strict_types=1);

namespace App\Application\Services\AI;

use App\Application\Contracts\Repositories\Ai\AiConversationRepositoryInterface;
use App\Application\DTOs\Ai\CreateConversationData;
use App\Domain\Ai\AiConversation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class ConversationService
{
    public function __construct(
        private readonly AiConversationRepositoryInterface $conversations,
        private readonly string $defaultModel,
    ) {}

    public function list(Authenticatable $actor, int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', AiConversation::class);

        return $this->conversations->paginateForUser($actor->getAuthIdentifier(), $perPage);
    }

    public function find(Authenticatable $actor, string $id): AiConversation
    {
        $conversation = $this->conversations->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $conversation);

        return $conversation;
    }

    public function create(Authenticatable $actor, CreateConversationData $data): AiConversation
    {
        Gate::forUser($actor)->authorize('create', AiConversation::class);

        return $this->conversations->create([
            'user_id' => $actor->getAuthIdentifier(),
            'title' => $data->title,
            'system_prompt' => $data->systemPrompt,
            'provider' => 'openai',
            'model' => $data->model ?? $this->defaultModel,
            'total_prompt_tokens' => 0,
            'total_completion_tokens' => 0,
        ]);
    }

    public function delete(Authenticatable $actor, string $id): void
    {
        $conversation = $this->conversations->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('delete', $conversation);

        $this->conversations->delete($id);
    }
}
