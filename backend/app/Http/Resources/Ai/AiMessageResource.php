<?php

declare(strict_types=1);

namespace App\Http\Resources\Ai;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AiMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'ai_message',
            'attributes' => [
                'role' => $this->role,
                'content' => $this->content,
                'tool_calls' => $this->toolCalls === null ? null : array_map(
                    fn ($call) => ['id' => $call->id, 'name' => $call->name, 'arguments' => $call->argumentsArray()],
                    $this->toolCalls
                ),
                'tool_call_id' => $this->toolCallId,
                'name' => $this->name,
                'prompt_tokens' => $this->promptTokens,
                'completion_tokens' => $this->completionTokens,
                'created_at' => $this->createdAt->format(DATE_ATOM),
            ],
        ];
    }
}
