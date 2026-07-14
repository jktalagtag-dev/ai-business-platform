<?php

declare(strict_types=1);

use App\Domain\KnowledgeBase\VectorMath;

it('returns 1.0 for identical vectors', function () {
    expect(VectorMath::cosineSimilarity([1.0, 2.0, 3.0], [1.0, 2.0, 3.0]))->toBe(1.0);
});

it('returns 0.0 for orthogonal vectors', function () {
    expect(VectorMath::cosineSimilarity([1.0, 0.0], [0.0, 1.0]))->toBe(0.0);
});

it('returns -1.0 for opposite vectors', function () {
    expect(VectorMath::cosineSimilarity([1.0, 0.0], [-1.0, 0.0]))->toBe(-1.0);
});

it('returns 0.0 when either vector is all zeros', function () {
    expect(VectorMath::cosineSimilarity([0.0, 0.0], [1.0, 2.0]))->toBe(0.0);
});
