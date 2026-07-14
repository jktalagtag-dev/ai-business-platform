<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services\AI;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A single function the assistant may call mid-conversation. Implementations
 * are tagged onto AiToolRegistry in AiServiceProvider — adding a new tool
 * never requires touching ChatService.
 */
interface AiToolInterface
{
    /**
     * The function name the model sees and calls by, e.g. "get_ticket_statistics".
     */
    public function name(): string;

    public function description(): string;

    /**
     * JSON Schema for this function's arguments, per the OpenAI tool-calling
     * spec, e.g. ['type' => 'object', 'properties' => [...], 'required' => [...]].
     *
     * @return array<string, mixed>
     */
    public function parameters(): array;

    /**
     * Executes the call and returns a JSON-serializable result, which is
     * fed back to the model as the content of a `tool` role message.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function handle(Authenticatable $actor, array $arguments): array;
}
