<?php

declare(strict_types=1);

namespace App\Domain\KnowledgeBase;

/**
 * Fixed-size sliding-window chunking with overlap — simple and portable
 * (no NLP/sentence-boundary dependency), chunk_size/overlap configurable
 * via config/knowledge_base.php.
 */
final class TextChunker
{
    /**
     * @return list<string>
     */
    public function chunk(string $text, int $chunkSize, int $overlap): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $chunkSize, $length);
            $piece = trim(mb_substr($text, $start, $end - $start));

            if ($piece !== '') {
                $chunks[] = $piece;
            }

            if ($end >= $length) {
                break;
            }

            // Guards against an infinite loop when overlap >= chunkSize —
            // start must always advance by at least one character.
            $start = max($end - $overlap, $start + 1);
        }

        return $chunks;
    }
}
