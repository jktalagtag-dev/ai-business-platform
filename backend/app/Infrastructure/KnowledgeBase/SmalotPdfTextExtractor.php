<?php

declare(strict_types=1);

namespace App\Infrastructure\KnowledgeBase;

use App\Application\Contracts\Services\KnowledgeBase\PdfTextExtractorInterface;
use Smalot\PdfParser\Parser;

final class SmalotPdfTextExtractor implements PdfTextExtractorInterface
{
    public function extractPages(string $absoluteFilePath): array
    {
        $pdf = (new Parser)->parseFile($absoluteFilePath);

        return array_map(
            fn ($page): string => trim($page->getText()),
            $pdf->getPages()
        );
    }
}
