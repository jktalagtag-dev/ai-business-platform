<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Contracts\Services\KnowledgeBase\PdfTextExtractorInterface;

/**
 * Test double for PdfTextExtractorInterface — returns canned page text
 * keyed by absolute file path, so Feature tests can exercise the upload
 * pipeline (chunking, embedding, persistence) without needing a real,
 * valid PDF binary fixture.
 */
final class FakePdfTextExtractor implements PdfTextExtractorInterface
{
    /**
     * @var array<string, list<string>>
     */
    private array $pagesByPath = [];

    /**
     * @var list<string>
     */
    private array $defaultPages = ['Fallback page text.'];

    /**
     * @param  list<string>  $pages
     */
    public function forPath(string $absoluteFilePath, array $pages): self
    {
        $this->pagesByPath[$absoluteFilePath] = $pages;

        return $this;
    }

    /**
     * @param  list<string>  $pages
     */
    public function forNextPath(array $pages): self
    {
        $this->defaultPages = $pages;

        return $this;
    }

    public function extractPages(string $absoluteFilePath): array
    {
        return $this->pagesByPath[$absoluteFilePath] ?? $this->defaultPages;
    }
}
