<?php

declare(strict_types=1);

namespace App\Application\Jobs\KnowledgeBase;

use App\Application\Contracts\Repositories\KnowledgeBase\DocumentChunkRepositoryInterface;
use App\Application\Contracts\Repositories\KnowledgeBase\DocumentRepositoryInterface;
use App\Application\Contracts\Services\AI\AiProviderInterface;
use App\Application\Contracts\Services\KnowledgeBase\PdfTextExtractorInterface;
use App\Domain\KnowledgeBase\TextChunker;
use App\Http\Support\RequestTenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Runs the whole upload pipeline for one document: extract text per page,
 * chunk each page, embed every chunk in one batch call, then persist —
 * chunks are only ever written once every embedding for the document has
 * succeeded, so a document is never left with a partial chunk set; on any
 * failure the document is marked 'failed' with the error captured instead.
 *
 * Queue workers run outside any HTTP request, so — like every other
 * Ticketing job before it — this job carries $tenantId explicitly and sets
 * it on RequestTenantContext itself before touching any tenant-scoped
 * repository.
 */
final class ProcessKnowledgeBaseDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $tenantId,
        private readonly string $documentId,
    ) {
        $this->onQueue('knowledge_base');
    }

    public function handle(
        RequestTenantContext $tenantContext,
        DocumentRepositoryInterface $documents,
        DocumentChunkRepositoryInterface $chunks,
        PdfTextExtractorInterface $extractor,
        AiProviderInterface $provider,
        TextChunker $chunker,
    ): void {
        $tenantContext->setTenantId($this->tenantId);

        $document = $documents->findById($this->documentId);

        if ($document === null) {
            return;
        }

        try {
            $pages = $extractor->extractPages(Storage::disk('public')->path($document->filePath));

            $pending = [];

            foreach ($pages as $pageIndex => $pageText) {
                foreach ($chunker->chunk($pageText, (int) config('knowledge_base.chunk_size'), (int) config('knowledge_base.chunk_overlap')) as $chunkText) {
                    $pending[] = ['page_number' => $pageIndex + 1, 'content' => $chunkText];
                }
            }

            if ($pending === []) {
                $documents->update($document->id, [
                    'status' => 'failed',
                    'error_message' => 'No extractable text was found in this document (it may be a scanned/image-only PDF).',
                ]);

                return;
            }

            $embeddings = $provider->embed(array_column($pending, 'content'));

            $rows = [];

            foreach ($pending as $index => $item) {
                $rows[] = [
                    'chunk_index' => $index,
                    'page_number' => $item['page_number'],
                    'content' => $item['content'],
                    'embedding' => $embeddings[$index],
                ];
            }

            $chunks->createMany($document->id, $rows);

            $documents->update($document->id, [
                'status' => 'ready',
                'page_count' => count($pages),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $documents->update($document->id, [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
