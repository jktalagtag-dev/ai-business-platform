<?php

declare(strict_types=1);

namespace App\Domain\Ai;

/**
 * A single function call the model requested inside an assistant message.
 * `argumentsJson` is kept as the raw string the model produced (OpenAI's
 * wire format never guarantees it round-trips through json_decode/encode
 * byte-for-byte) and only decoded on demand via argumentsArray().
 *
 * `thoughtSignature` is an opaque, Gemini-specific value its OpenAI-compat
 * layer requires echoed back verbatim on a tool_call when replaying it in a
 * later request, or it rejects the call with a 400. Null for every other
 * provider (OpenAI, OpenRouter, Ollama, ...).
 */
final class ToolCall
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $argumentsJson,
        public readonly ?string $thoughtSignature = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function argumentsArray(): array
    {
        return json_decode($this->argumentsJson, true) ?? [];
    }
}
