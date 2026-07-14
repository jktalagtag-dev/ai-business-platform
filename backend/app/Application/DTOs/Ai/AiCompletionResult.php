<?php

declare(strict_types=1);

namespace App\Application\DTOs\Ai;

use App\Domain\Ai\ToolCall;

final class AiCompletionResult
{
    /**
     * @param  list<ToolCall>  $toolCalls
     */
    public function __construct(
        public readonly ?string $content,
        public readonly array $toolCalls,
        public readonly ?int $promptTokens,
        public readonly ?int $completionTokens,
    ) {}

    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }
}
