<?php

declare(strict_types=1);

namespace App\Domain\Ai;

final class AiMessage
{
    /**
     * @param  list<ToolCall>|null  $toolCalls
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $conversationId,
        public readonly string $role,
        public readonly ?string $content,
        public readonly ?array $toolCalls,
        public readonly ?string $toolCallId,
        public readonly ?string $name,
        public readonly ?int $promptTokens,
        public readonly ?int $completionTokens,
        public readonly \DateTimeImmutable $createdAt,
    ) {}

    /**
     * Renders this message back into the shape the OpenAI-compatible
     * `/chat/completions` API expects in its `messages` array.
     *
     * @return array<string, mixed>
     */
    public function toProviderFormat(): array
    {
        $message = ['role' => $this->role];

        if ($this->content !== null) {
            $message['content'] = $this->content;
        }

        if ($this->toolCalls !== null) {
            $message['tool_calls'] = array_map(function (ToolCall $call): array {
                $entry = [
                    'id' => $call->id,
                    'type' => 'function',
                    'function' => ['name' => $call->name, 'arguments' => $call->argumentsJson],
                ];

                if ($call->thoughtSignature !== null) {
                    $entry['extra_content'] = ['google' => ['thought_signature' => $call->thoughtSignature]];
                }

                return $entry;
            }, $this->toolCalls);
        }

        if ($this->toolCallId !== null) {
            $message['tool_call_id'] = $this->toolCallId;
        }

        if ($this->name !== null) {
            $message['name'] = $this->name;
        }

        return $message;
    }
}
