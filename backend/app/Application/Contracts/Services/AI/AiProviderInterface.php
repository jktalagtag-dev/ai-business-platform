<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services\AI;

use App\Application\DTOs\Ai\AiCompletionResult;
use App\Domain\Shared\Exceptions\AiProviderException;

/**
 * Speaks the OpenAI Chat Completions wire format (`/chat/completions`),
 * which any OpenAI-compatible endpoint (OpenAI itself, or a compatible
 * gateway/self-hosted server) implements — the concrete adapter is
 * swappable via config/ai.php without touching Application code.
 */
interface AiProviderInterface
{
    /**
     * Streams a single chat completion turn.
     *
     * @param  list<array<string, mixed>>  $messages  OpenAI-format message array (system/user/assistant/tool)
     * @param  list<array<string, mixed>>  $tools  OpenAI-format tool/function definitions; empty if none are offered
     * @param  callable(string):void  $onDelta  invoked with each incremental content fragment as it streams in
     *
     * @throws AiProviderException
     */
    public function stream(array $messages, array $tools, callable $onDelta): AiCompletionResult;

    /**
     * Embeds a batch of texts in one call, returning one vector per input
     * text in the same order — backs the Knowledge Base module's chunk and
     * query embeddings (see config('ai.embedding_model')).
     *
     * @param  list<string>  $texts
     * @return list<list<float>>
     *
     * @throws AiProviderException
     */
    public function embed(array $texts): array;
}
