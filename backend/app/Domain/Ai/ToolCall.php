<?php

declare(strict_types=1);

namespace App\Domain\Ai;

/**
 * A single function call the model requested inside an assistant message.
 * `argumentsJson` is kept as the raw string the model produced (OpenAI's
 * wire format never guarantees it round-trips through json_decode/encode
 * byte-for-byte) and only decoded on demand via argumentsArray().
 */
final class ToolCall
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $argumentsJson,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function argumentsArray(): array
    {
        return json_decode($this->argumentsJson, true) ?? [];
    }
}
