<?php

declare(strict_types=1);

namespace App\Application\Services\KnowledgeBase;

use App\Application\Contracts\Repositories\KnowledgeBase\DocumentChunkRepositoryInterface;
use App\Application\Contracts\Services\AI\AiProviderInterface;
use App\Application\DTOs\KnowledgeBase\RetrievedChunk;
use App\Domain\KnowledgeBase\Document;
use App\Domain\KnowledgeBase\VectorMath;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class KnowledgeBaseRetrievalService
{
    public function __construct(
        private readonly DocumentChunkRepositoryInterface $chunks,
        private readonly AiProviderInterface $provider,
    ) {}

    /**
     * Embeds $query, then ranks every chunk in the tenant's knowledge base
     * by cosine similarity — brute-force (see VectorMath), not an ANN
     * index; fine at typical internal-KB volumes.
     *
     * @return list<RetrievedChunk>
     */
    public function search(Authenticatable $actor, string $query, ?int $topK = null): array
    {
        Gate::forUser($actor)->authorize('viewAny', Document::class);

        $topK ??= (int) config('knowledge_base.top_k');

        $allChunks = $this->chunks->allForTenant();

        if ($allChunks === []) {
            return [];
        }

        $queryEmbedding = $this->provider->embed([$query])[0];

        $scored = collect($allChunks)
            ->map(fn ($chunk) => new RetrievedChunk($chunk, VectorMath::cosineSimilarity($queryEmbedding, $chunk->embedding)))
            ->sortByDesc('score')
            ->take($topK)
            ->values();

        return $scored->all();
    }
}
