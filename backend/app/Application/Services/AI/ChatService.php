<?php

declare(strict_types=1);

namespace App\Application\Services\AI;

use App\Application\Contracts\Repositories\Ai\AiConversationRepositoryInterface;
use App\Application\Contracts\Repositories\Ai\AiMessageRepositoryInterface;
use App\Application\Contracts\Services\AI\AiProviderInterface;
use App\Domain\Ai\AiConversation;
use App\Domain\Ai\AiMessage;
use App\Domain\Ai\ToolCall;
use App\Domain\Shared\Exceptions\AiToolIterationLimitExceededException;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Runs one user turn to completion: persists the user message, builds the
 * context-window request, streams the model's reply, and — if the model
 * requests function calls — executes them via AiToolRegistry and loops
 * back to the model with the results, up to max_tool_iterations.
 *
 * Assumes the caller has already authorized access to $conversation (see
 * ConversationService::find(), which runs the 'sendMessage' Policy check).
 * This method does no authorization of its own so that check can happen
 * — and fail cleanly with a normal JSON 403 — before any streaming output
 * is written to the response.
 */
final class ChatService
{
    public function __construct(
        private readonly AiConversationRepositoryInterface $conversations,
        private readonly AiMessageRepositoryInterface $messages,
        private readonly AiProviderInterface $provider,
        private readonly AiToolRegistry $tools,
        private readonly string $defaultSystemPrompt,
        private readonly int $contextWindowMessages,
        private readonly int $maxToolIterations,
    ) {}

    /**
     * @param  callable(string, array<string, mixed>):void  $emit  SSE event emitter: $emit($event, $data)
     */
    public function reply(Authenticatable $actor, AiConversation $conversation, string $userContent, callable $emit): void
    {
        $userMessage = $this->messages->create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userContent,
            'tool_calls' => null,
            'tool_call_id' => null,
            'name' => null,
            'prompt_tokens' => null,
            'completion_tokens' => null,
        ]);

        $emit('user_message', ['id' => $userMessage->id, 'content' => $userMessage->content]);

        $providerMessages = $this->buildContextWindow($conversation);
        $toolDefinitions = $this->tools->definitions();

        $sessionPromptTokens = 0;
        $sessionCompletionTokens = 0;

        for ($iteration = 0; $iteration < $this->maxToolIterations; $iteration++) {
            $result = $this->provider->stream(
                $providerMessages,
                $toolDefinitions,
                fn (string $delta) => $emit('delta', ['content' => $delta])
            );

            $sessionPromptTokens += $result->promptTokens ?? 0;
            $sessionCompletionTokens += $result->completionTokens ?? 0;

            if (! $result->hasToolCalls()) {
                $assistantMessage = $this->messages->create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $result->content,
                    'tool_calls' => null,
                    'tool_call_id' => null,
                    'name' => null,
                    'prompt_tokens' => $result->promptTokens,
                    'completion_tokens' => $result->completionTokens,
                ]);

                $this->conversations->update($conversation->id, [
                    'total_prompt_tokens' => $conversation->totalPromptTokens + $sessionPromptTokens,
                    'total_completion_tokens' => $conversation->totalCompletionTokens + $sessionCompletionTokens,
                ]);

                $emit('message', [
                    'id' => $assistantMessage->id,
                    'content' => $assistantMessage->content,
                    'usage' => [
                        'prompt_tokens' => $sessionPromptTokens,
                        'completion_tokens' => $sessionCompletionTokens,
                    ],
                ]);

                return;
            }

            $assistantToolCallMessage = $this->messages->create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $result->content,
                'tool_calls' => array_map(
                    fn (ToolCall $c): array => ['id' => $c->id, 'name' => $c->name, 'arguments' => $c->argumentsJson],
                    $result->toolCalls
                ),
                'tool_call_id' => null,
                'name' => null,
                'prompt_tokens' => $result->promptTokens,
                'completion_tokens' => $result->completionTokens,
            ]);
            $providerMessages[] = $assistantToolCallMessage->toProviderFormat();

            foreach ($result->toolCalls as $call) {
                $emit('tool_call', ['id' => $call->id, 'name' => $call->name, 'arguments' => $call->argumentsArray()]);

                $toolResult = $this->tools->dispatch($actor, $call->name, $call->argumentsArray());

                $emit('tool_result', ['id' => $call->id, 'name' => $call->name, 'result' => $toolResult]);

                $toolMessage = $this->messages->create([
                    'conversation_id' => $conversation->id,
                    'role' => 'tool',
                    'content' => json_encode($toolResult),
                    'tool_calls' => null,
                    'tool_call_id' => $call->id,
                    'name' => $call->name,
                    'prompt_tokens' => null,
                    'completion_tokens' => null,
                ]);
                $providerMessages[] = $toolMessage->toProviderFormat();
            }
        }

        throw new AiToolIterationLimitExceededException($this->maxToolIterations);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildContextWindow(AiConversation $conversation): array
    {
        $history = $this->messages->recentForConversation($conversation->id, $this->contextWindowMessages);

        return [
            ['role' => 'system', 'content' => $conversation->systemPromptOrDefault($this->defaultSystemPrompt)],
            ...array_map(fn (AiMessage $m): array => $m->toProviderFormat(), $history),
        ];
    }
}
