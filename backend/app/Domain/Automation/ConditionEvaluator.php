<?php

declare(strict_types=1);

namespace App\Domain\Automation;

/**
 * Pure Domain logic — no framework/DB dependency — evaluating a
 * workflow_steps condition config against the flat event/trigger context
 * captured on an automation_jobs row.
 */
final class ConditionEvaluator
{
    public const OPERATORS = ['equals', 'not_equals', 'contains', 'greater_than', 'less_than'];

    /**
     * @param  array{field: string, operator: string, value: mixed}  $condition
     * @param  array<string, mixed>  $context
     */
    public static function evaluate(array $condition, array $context): bool
    {
        $value = self::resolveField($context, $condition['field']);
        $target = $condition['value'];

        return match ($condition['operator']) {
            'equals' => $value == $target,
            'not_equals' => $value != $target,
            'contains' => is_string($value) && str_contains($value, (string) $target),
            'greater_than' => is_numeric($value) && is_numeric($target) && (float) $value > (float) $target,
            'less_than' => is_numeric($value) && is_numeric($target) && (float) $value < (float) $target,
            default => false,
        };
    }

    /**
     * Resolves a dot-path (e.g. "ticket.priority") against a nested
     * context array. Returns null if any segment is missing.
     *
     * @param  array<string, mixed>  $context
     */
    public static function resolveField(array $context, string $path): mixed
    {
        $value = $context;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
