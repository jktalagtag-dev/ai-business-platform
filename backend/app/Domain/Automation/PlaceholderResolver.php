<?php

declare(strict_types=1);

namespace App\Domain\Automation;

/**
 * Substitutes {{field.path}} placeholders in action config strings (e.g.
 * "New ticket: {{ticket.subject}}") with values resolved from the event
 * context via ConditionEvaluator::resolveField(). Non-scalar or missing
 * values render as an empty string rather than leaving the placeholder or
 * throwing — a workflow author's typo shouldn't crash a run.
 */
final class PlaceholderResolver
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function render(string $template, array $context): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function (array $matches) use ($context): string {
                $value = ConditionEvaluator::resolveField($context, $matches[1]);

                return is_scalar($value) ? (string) $value : '';
            },
            $template
        );
    }

    /**
     * Recursively renders every string value in a config array — used to
     * resolve placeholders across an entire action config in one call.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function renderConfig(array $config, array $context): array
    {
        return array_map(
            fn (mixed $value): mixed => match (true) {
                is_string($value) => self::render($value, $context),
                is_array($value) => self::renderConfig($value, $context),
                default => $value,
            },
            $config
        );
    }
}
