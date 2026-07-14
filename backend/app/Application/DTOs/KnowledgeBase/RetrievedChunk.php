<?php

declare(strict_types=1);

namespace App\Application\DTOs\KnowledgeBase;

use App\Domain\KnowledgeBase\DocumentChunk;

final class RetrievedChunk
{
    public function __construct(
        public readonly DocumentChunk $chunk,
        public readonly float $score,
    ) {}
}
