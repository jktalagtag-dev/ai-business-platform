<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services\KnowledgeBase;

interface PdfTextExtractorInterface
{
    /**
     * Extracts the text content of each page, in order. Implementations
     * are text-extraction only — scanned/image-only PDFs with no embedded
     * text layer will yield empty (or near-empty) pages, not OCR results.
     *
     * @return list<string>
     */
    public function extractPages(string $absoluteFilePath): array;
}
