<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\KnowledgeBase;

use App\Application\Contracts\Repositories\KnowledgeBase\DocumentChunkRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\KnowledgeBase\DocumentChunk as DocumentChunkEntity;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeBase\DocumentChunk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DocumentChunkRepository implements DocumentChunkRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function allForTenant(): array
    {
        return DocumentChunk::where('tenant_id', $this->tenantContext->tenantId())
            ->get()
            ->map(fn (DocumentChunk $c): DocumentChunkEntity => $this->toDomain($c))
            ->all();
    }

    public function countForDocument(string $documentId): int
    {
        return DocumentChunk::where('tenant_id', $this->tenantContext->tenantId())
            ->where('document_id', $documentId)
            ->count();
    }

    public function createMany(string $documentId, array $chunks): void
    {
        $tenantId = $this->tenantContext->tenantId();
        $now = now();

        DB::table('kb_document_chunks')->insert(array_map(
            fn (array $chunk): array => [
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
                'document_id' => $documentId,
                'chunk_index' => $chunk['chunk_index'],
                'page_number' => $chunk['page_number'],
                'content' => $chunk['content'],
                'embedding' => json_encode($chunk['embedding']),
                'created_at' => $now,
            ],
            $chunks
        ));
    }

    private function toDomain(DocumentChunk $chunk): DocumentChunkEntity
    {
        return new DocumentChunkEntity(
            id: $chunk->id,
            tenantId: $chunk->tenant_id,
            documentId: $chunk->document_id,
            chunkIndex: $chunk->chunk_index,
            pageNumber: $chunk->page_number,
            content: $chunk->content,
            embedding: $chunk->embedding,
            createdAt: \DateTimeImmutable::createFromInterface($chunk->created_at),
        );
    }
}
