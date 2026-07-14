<?php

declare(strict_types=1);

use App\Domain\Ticket\SlaPolicy;

it('maps each priority to its resolution-time target in minutes', function () {
    $policy = new SlaPolicy;

    expect($policy->resolutionTargetMinutes('critical'))->toBe(4 * 60);
    expect($policy->resolutionTargetMinutes('high'))->toBe(8 * 60);
    expect($policy->resolutionTargetMinutes('medium'))->toBe(24 * 60);
    expect($policy->resolutionTargetMinutes('low'))->toBe(72 * 60);
});

it('falls back to the medium target for an unrecognized priority', function () {
    $policy = new SlaPolicy;

    expect($policy->resolutionTargetMinutes('unknown'))->toBe(24 * 60);
});

it('is not breached before the resolution-time target elapses', function () {
    $policy = new SlaPolicy;
    $createdAt = new DateTimeImmutable('2026-01-01 00:00:00');
    $now = $createdAt->modify('+3 hours');

    expect($policy->isBreached('critical', $createdAt, $now))->toBeFalse();
});

it('is breached once the resolution-time target has elapsed', function () {
    $policy = new SlaPolicy;
    $createdAt = new DateTimeImmutable('2026-01-01 00:00:00');
    $now = $createdAt->modify('+5 hours');

    expect($policy->isBreached('critical', $createdAt, $now))->toBeTrue();
});
