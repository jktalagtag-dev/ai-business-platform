<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\KnowledgeBase;

use App\Application\Contracts\Repositories\KnowledgeBase\DocumentRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\KnowledgeBase\Document as DocumentEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeBase\Document;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class DocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForTenant(int $perPage = 25): CursorPaginator
    {
        $query = $this->scoped()
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($query, fn (Document $d): DocumentEntity => $this->toDomain($d));
    }

    public function findById(string $id): ?DocumentEntity
    {
        $document = $this->scoped()->find($id);

        return $document ? $this->toDomain($document) : null;
    }

    public function create(array $attributes): DocumentEntity
    {
        $document = Document::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($document);
    }

    public function update(string $id, array $attributes): DocumentEntity
    {
        $document = $this->scoped()->findOrFail($id);
        $document->fill($attributes)->save();

        return $this->toDomain($document);
    }

    public function delete(string $id): void
    {
        $this->scoped()->findOrFail($id)->delete();
    }

    private function scoped(): Builder
    {
        return Document::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(Document $document): DocumentEntity
    {
        return new DocumentEntity(
            id: $document->id,
            tenantId: $document->tenant_id,
            uploadedByUserId: $document->uploaded_by_user_id,
            title: $document->title,
            originalFilename: $document->original_filename,
            filePath: $document->file_path,
            mimeType: $document->mime_type,
            sizeBytes: $document->size_bytes,
            status: $document->status,
            errorMessage: $document->error_message,
            pageCount: $document->page_count,
            createdAt: \DateTimeImmutable::createFromInterface($document->created_at),
            updatedAt: \DateTimeImmutable::createFromInterface($document->updated_at),
        );
    }
}
