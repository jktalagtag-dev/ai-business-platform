<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Ai;

use App\Application\Contracts\Repositories\Ai\AiMessageRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Ai\AiMessage as AiMessageEntity;
use App\Domain\Ai\ToolCall;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Ai\AiMessage;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class AiMessageRepository implements AiMessageRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForConversation(string $conversationId, int $perPage = 50): CursorPaginator
    {
        $query = $this->scoped()
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($query, fn (AiMessage $m): AiMessageEntity => $this->toDomain($m));
    }

    public function recentForConversation(string $conversationId, int $limit): array
    {
        return $this->scoped()
            ->where('conversation_id', $conversationId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (AiMessage $m): AiMessageEntity => $this->toDomain($m))
            ->all();
    }

    public function create(array $attributes): AiMessageEntity
    {
        $message = AiMessage::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($message);
    }

    private function scoped(): Builder
    {
        return AiMessage::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(AiMessage $message): AiMessageEntity
    {
        return new AiMessageEntity(
            id: $message->id,
            tenantId: $message->tenant_id,
            conversationId: $message->conversation_id,
            role: $message->role,
            content: $message->content,
            toolCalls: $message->tool_calls === null ? null : array_map(
                fn (array $call): ToolCall => new ToolCall(
                    id: $call['id'],
                    name: $call['name'],
                    argumentsJson: $call['arguments'],
                ),
                $message->tool_calls
            ),
            toolCallId: $message->tool_call_id,
            name: $message->name,
            promptTokens: $message->prompt_tokens,
            completionTokens: $message->completion_tokens,
            createdAt: \DateTimeImmutable::createFromInterface($message->created_at),
        );
    }
}
