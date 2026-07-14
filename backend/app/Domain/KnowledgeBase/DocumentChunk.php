<?php

declare(strict_types=1);

namespace App\Domain\KnowledgeBase;

final class DocumentChunk
{
    /**
     * @param  list<float>  $embedding
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $documentId,
        public readonly int $chunkIndex,
        public readonly ?int $pageNumber,
        public readonly string $content,
        public readonly array $embedding,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
