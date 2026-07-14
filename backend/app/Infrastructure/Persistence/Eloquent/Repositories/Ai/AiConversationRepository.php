<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Ai;

use App\Application\Contracts\Repositories\Ai\AiConversationRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Ai\AiConversation as AiConversationEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Ai\AiConversation;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class AiConversationRepository implements AiConversationRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForUser(string $userId, int $perPage = 25): CursorPaginator
    {
        $query = $this->scoped()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($query, fn (AiConversation $c): AiConversationEntity => $this->toDomain($c));
    }

    public function findById(string $id): ?AiConversationEntity
    {
        $conversation = $this->scoped()->find($id);

        return $conversation ? $this->toDomain($conversation) : null;
    }

    public function create(array $attributes): AiConversationEntity
    {
        $conversation = AiConversation::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($conversation);
    }

    public function update(string $id, array $attributes): AiConversationEntity
    {
        $conversation = $this->scoped()->findOrFail($id);
        $conversation->fill($attributes)->save();

        return $this->toDomain($conversation);
    }

    public function delete(string $id): void
    {
        $this->scoped()->findOrFail($id)->delete();
    }

    private function scoped(): Builder
    {
        return AiConversation::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(AiConversation $conversation): AiConversationEntity
    {
        return new AiConversationEntity(
            id: $conversation->id,
            tenantId: $conversation->tenant_id,
            userId: $conversation->user_id,
            title: $conversation->title,
            systemPrompt: $conversation->system_prompt,
            provider: $conversation->provider,
            model: $conversation->model,
            totalPromptTokens: $conversation->total_prompt_tokens,
            totalCompletionTokens: $conversation->total_completion_tokens,
            createdAt: \DateTimeImmutable::createFromInterface($conversation->created_at),
            updatedAt: \DateTimeImmutable::createFromInterface($conversation->updated_at),
        );
    }
}
