<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\KnowledgeBase;

use App\Domain\KnowledgeBase\DocumentChunk;

interface DocumentChunkRepositoryInterface
{
    /**
     * Every chunk for the current tenant, used for brute-force similarity
     * search (see Domain\KnowledgeBase\VectorMath). Chunks only ever exist
     * for a document that finished processing successfully, so there is no
     * separate "ready" filter to apply here.
     *
     * @return list<DocumentChunk>
     */
    public function allForTenant(): array;

    public function countForDocument(string $documentId): int;

    /**
     * @param  list<array{chunk_index: int, page_number: ?int, content: string, embedding: list<float>}>  $chunks
     */
    public function createMany(string $documentId, array $chunks): void;
}
