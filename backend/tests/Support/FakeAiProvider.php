<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Contracts\Services\AI\AiProviderInterface;
use App\Application\DTOs\Ai\AiCompletionResult;
use App\Domain\Ai\ToolCall;

/**
 * Test double for AiProviderInterface — queues canned completions (plain
 * replies or tool calls) so Feature tests can exercise ChatService's
 * context-window building, tool-call loop, and token tracking without any
 * real network call to an OpenAI-compatible endpoint.
 */
final class FakeAiProvider implements AiProviderInterface
{
    /**
     * @var list<AiCompletionResult>
     */
    private array $queue = [];

    /**
     * @var list<array{messages: list<array<string, mixed>>, tools: list<array<string, mixed>>}>
     */
    public array $calls = [];

    public function queueTextReply(string $content, int $promptTokens = 10, int $completionTokens = 5): self
    {
        $this->queue[] = new AiCompletionResult($content, [], $promptTokens, $completionTokens);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function queueToolCall(string $id, string $name, array $arguments, int $promptTokens = 10, int $completionTokens = 5): self
    {
        $this->queue[] = new AiCompletionResult(
            null,
            [new ToolCall($id, $name, json_encode($arguments))],
            $promptTokens,
            $completionTokens
        );

        return $this;
    }

    public function stream(array $messages, array $tools, callable $onDelta): AiCompletionResult
    {
        $this->calls[] = ['messages' => $messages, 'tools' => $tools];

        $result = array_shift($this->queue) ?? new AiCompletionResult('', [], 0, 0);

        if ($result->content !== null && $result->content !== '') {
            $onDelta($result->content);
        }

        return $result;
    }

    /**
     * Deterministic, word-overlap-sensitive fake embeddings (feature
     * hashing into a fixed-size vector) — no real network call, but similar
     * texts still score higher on cosine similarity than dissimilar ones,
     * which is what Knowledge Base retrieval tests need to assert ranking.
     */
    public function embed(array $texts): array
    {
        return array_map([$this, 'deterministicEmbedding'], $texts);
    }

    /**
     * @return list<float>
     */
    private function deterministicEmbedding(string $text): array
    {
        $dimensions = 32;
        $vector = array_fill(0, $dimensions, 0.0);

        foreach (preg_split('/\W+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $vector[crc32($word) % $dimensions] += 1.0;
        }

        $norm = sqrt(array_sum(array_map(fn (float $v): float => $v * $v, $vector))) ?: 1.0;

        return array_map(fn (float $v): float => $v / $norm, $vector);
    }
}
