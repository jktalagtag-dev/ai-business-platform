<?php

declare(strict_types=1);

namespace App\Domain\KnowledgeBase;

/**
 * Pure Domain logic — no framework/DB dependency — backing brute-force
 * similarity search over kb_document_chunks.embedding (see the migration's
 * comment for why this isn't a native pgvector column).
 */
final class VectorMath
{
    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $length = min(count($a), count($b));

        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
