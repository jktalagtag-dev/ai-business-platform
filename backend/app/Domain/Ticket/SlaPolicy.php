<?php

declare(strict_types=1);

namespace App\Domain\Ticket;

/**
 * Maps ticket priority to its resolution-time target. Pure Domain logic —
 * no framework/DB dependency — used by the SLA Monitoring job to decide
 * which open tickets are breaching (or about to breach) their SLA and need
 * an escalation reminder, and available for the dashboard's "Average
 * Resolution Time" statistic to be compared against.
 */
final class SlaPolicy
{
    private const RESOLUTION_TARGET_MINUTES = [
        'critical' => 4 * 60,
        'high' => 8 * 60,
        'medium' => 24 * 60,
        'low' => 72 * 60,
    ];

    public function resolutionTargetMinutes(string $priority): int
    {
        return self::RESOLUTION_TARGET_MINUTES[$priority] ?? self::RESOLUTION_TARGET_MINUTES['medium'];
    }

    public function isBreached(string $priority, \DateTimeImmutable $createdAt, \DateTimeImmutable $now): bool
    {
        $elapsedMinutes = ($now->getTimestamp() - $createdAt->getTimestamp()) / 60;

        return $elapsedMinutes >= $this->resolutionTargetMinutes($priority);
    }
}
