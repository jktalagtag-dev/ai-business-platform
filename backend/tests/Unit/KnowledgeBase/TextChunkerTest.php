<?php

declare(strict_types=1);

use App\Domain\KnowledgeBase\TextChunker;

it('returns no chunks for empty text', function () {
    expect((new TextChunker)->chunk('   ', 100, 10))->toBe([]);
});

it('returns a single chunk when text fits within chunk_size', function () {
    $chunker = new TextChunker;

    expect($chunker->chunk('short text', 100, 10))->toBe(['short text']);
});

it('splits long text into overlapping chunks', function () {
    $chunker = new TextChunker;
    $text = str_repeat('a', 25);

    $chunks = $chunker->chunk($text, 10, 3);

    expect($chunks)->not->toBeEmpty();
    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk))->toBeLessThanOrEqual(10);
    }
    // Every chunk boundary but the last should overlap the next by ~3 chars.
    expect(count($chunks))->toBeGreaterThan(1);
});

it('never loops infinitely when overlap is greater than or equal to chunk_size', function () {
    $chunker = new TextChunker;
    $text = str_repeat('b', 30);

    $chunks = $chunker->chunk($text, 5, 10);

    expect($chunks)->not->toBeEmpty();
});
