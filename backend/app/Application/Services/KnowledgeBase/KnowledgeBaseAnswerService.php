<?php

declare(strict_types=1);

namespace App\Application\Services\KnowledgeBase;

use App\Application\Contracts\Services\AI\AiProviderInterface;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Retrieval-augmented answer generation: retrieves the top-K relevant
 * chunks, builds a numbered-context prompt instructing the model to answer
 * only from that context and cite it inline (`[1]`, `[2]`, ...), and
 * returns the answer alongside a structured citation list (via
 * CitationBuilder) the caller can render as source links.
 */
final class KnowledgeBaseAnswerService
{
    public function __construct(
        private readonly KnowledgeBaseRetrievalService $retrieval,
        private readonly CitationBuilder $citations,
        private readonly AiProviderInterface $provider,
    ) {}

    /**
     * @return array{
     *     answer: string,
     *     citations: list<array{number: int, document_id: string, title: string, chunk_index: int, page_number: ?int, snippet: string, score: float}>,
     *     prompt_tokens: ?int, completion_tokens: ?int
     * }
     */
    public function ask(Authenticatable $actor, string $query, ?int $topK = null): array
    {
        $retrieved = $this->retrieval->search($actor, $query, $topK);

        if ($retrieved === []) {
            return [
                'answer' => "I don't have any knowledge base content to answer that yet.",
                'citations' => [],
                'prompt_tokens' => null,
                'completion_tokens' => null,
            ];
        }

        $citations = $this->citations->build($retrieved);

        $contextBlocks = [];

        foreach ($retrieved as $index => $retrievedChunk) {
            $citation = $citations[$index];
            $pageNote = $citation['page_number'] !== null ? ", page {$citation['page_number']}" : '';
            $contextBlocks[] = "[{$citation['number']}] (Document: \"{$citation['title']}\"{$pageNote})\n{$retrievedChunk->chunk->content}";
        }

        $systemPrompt = "You are a knowledge base assistant. Answer the user's question using ONLY the numbered "
            .'context blocks below. Cite sources inline using [1], [2], etc. matching the context block numbers. '
            ."If the answer isn't contained in the context, say you don't know rather than guessing.\n\n"
            .implode("\n\n", $contextBlocks);

        $result = $this->provider->stream(
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $query],
            ],
            [],
            static function (string $delta): void {}
        );

        return [
            'answer' => $result->content ?? '',
            'citations' => $citations,
            'prompt_tokens' => $result->promptTokens,
            'completion_tokens' => $result->completionTokens,
        ];
    }
}
