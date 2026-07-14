<?php

declare(strict_types=1);

namespace App\Application\Services\KnowledgeBase;

use App\Application\Contracts\Repositories\KnowledgeBase\DocumentRepositoryInterface;
use App\Application\DTOs\KnowledgeBase\RetrievedChunk;
use Illuminate\Support\Str;

/**
 * Shared by KnowledgeBaseAnswerService (the standalone /ask endpoint) and
 * SearchKnowledgeBaseTool (the AI Assistant chat's function-calling tool)
 * so both surfaces cite retrieved chunks the same way.
 */
final class CitationBuilder
{
    public function __construct(private readonly DocumentRepositoryInterface $documents) {}

    /**
     * @param  list<RetrievedChunk>  $retrieved
     * @return list<array{number: int, document_id: string, title: string, chunk_index: int, page_number: ?int, snippet: string, score: float}>
     */
    public function build(array $retrieved): array
    {
        $titlesByDocumentId = [];
        $citations = [];

        foreach ($retrieved as $index => $retrievedChunk) {
            $chunk = $retrievedChunk->chunk;
            $titlesByDocumentId[$chunk->documentId] ??= $this->documents->findById($chunk->documentId)?->title ?? 'Untitled document';

            $citations[] = [
                'number' => $index + 1,
                'document_id' => $chunk->documentId,
                'title' => $titlesByDocumentId[$chunk->documentId],
                'chunk_index' => $chunk->chunkIndex,
                'page_number' => $chunk->pageNumber,
                'snippet' => Str::limit($chunk->content, 240),
                'score' => round($retrievedChunk->score, 4),
            ];
        }

        return $citations;
    }
}
