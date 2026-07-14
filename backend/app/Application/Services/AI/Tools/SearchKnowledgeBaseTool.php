<?php

declare(strict_types=1);

namespace App\Application\Services\AI\Tools;

use App\Application\Contracts\Services\AI\AiToolInterface;
use App\Application\Services\KnowledgeBase\CitationBuilder;
use App\Application\Services\KnowledgeBase\KnowledgeBaseRetrievalService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Lets the AI Assistant chat cite the Knowledge Base directly via function
 * calling — delegates to the same KnowledgeBaseRetrievalService and
 * CitationBuilder the standalone /v1/knowledge-base/ask endpoint uses, so
 * results and authorization are identical either way.
 */
final class SearchKnowledgeBaseTool implements AiToolInterface
{
    public function __construct(
        private readonly KnowledgeBaseRetrievalService $retrieval,
        private readonly CitationBuilder $citations,
    ) {}

    public function name(): string
    {
        return 'search_knowledge_base';
    }

    public function description(): string
    {
        return 'Searches the internal knowledge base (uploaded documents) for content relevant to a query. Returns excerpts with document titles and page numbers for citation.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'The question or topic to search the knowledge base for'],
            ],
            'required' => ['query'],
        ];
    }

    public function handle(Authenticatable $actor, array $arguments): array
    {
        $retrieved = $this->retrieval->search($actor, (string) ($arguments['query'] ?? ''));

        return ['results' => $this->citations->build($retrieved)];
    }
}
