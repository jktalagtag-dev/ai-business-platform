<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Ai;

use App\Domain\Ai\AiMessage;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface AiMessageRepositoryInterface
{
    public function paginateForConversation(string $conversationId, int $perPage = 50): CursorPaginator;

    /**
     * The most recent $limit messages for a conversation, oldest first —
     * the "context memory" window sent to the model on each turn.
     *
     * @return list<AiMessage>
     */
    public function recentForConversation(string $conversationId, int $limit): array;

    /**
     * @param  array{
     *     conversation_id: string, role: string, content: ?string,
     *     tool_calls: ?array<int, array{id: string, name: string, arguments: string}>,
     *     tool_call_id: ?string, name: ?string,
     *     prompt_tokens: ?int, completion_tokens: ?int
     * }  $attributes
     */
    public function create(array $attributes): AiMessage;
}
