<?php

declare(strict_types=1);

namespace App\Application\Services\KnowledgeBase;

use App\Application\Contracts\Repositories\KnowledgeBase\DocumentRepositoryInterface;
use App\Application\Jobs\KnowledgeBase\ProcessKnowledgeBaseDocumentJob;
use App\Domain\KnowledgeBase\Document;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class DocumentService
{
    public function __construct(private readonly DocumentRepositoryInterface $documents) {}

    public function list(Authenticatable $actor, int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Document::class);

        return $this->documents->paginateForTenant($perPage);
    }

    public function find(Authenticatable $actor, string $id): Document
    {
        $document = $this->documents->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $document);

        return $document;
    }

    public function upload(
        Authenticatable $actor,
        string $title,
        string $storedPath,
        string $originalFilename,
        string $mimeType,
        int $sizeBytes,
    ): Document {
        Gate::forUser($actor)->authorize('create', Document::class);

        $document = $this->documents->create([
            'uploaded_by_user_id' => $actor->getAuthIdentifier(),
            'title' => $title,
            'original_filename' => $originalFilename,
            'file_path' => $storedPath,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'status' => 'processing',
            'error_message' => null,
            'page_count' => null,
        ]);

        ProcessKnowledgeBaseDocumentJob::dispatch($document->tenantId, $document->id);

        // Re-fetch rather than returning the entity captured above: under
        // the real (async) queue this still reflects 'processing', exactly
        // as it should; under the 'sync' driver used in tests, the job has
        // already run by the time dispatch() returns, so this picks up its
        // final status instead of the now-stale in-memory snapshot.
        return $this->documents->findById($document->id) ?? $document;
    }

    public function delete(Authenticatable $actor, string $id): void
    {
        $document = $this->documents->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('delete', $document);

        $this->documents->delete($id);
    }
}
