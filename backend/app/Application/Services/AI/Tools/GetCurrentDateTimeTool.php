<?php

declare(strict_types=1);

namespace App\Application\Services\AI\Tools;

use App\Application\Contracts\Services\AI\AiToolInterface;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Trivial, auth-free tool — mainly proves the function-calling mechanism
 * end-to-end (models otherwise have no notion of "now").
 */
final class GetCurrentDateTimeTool implements AiToolInterface
{
    public function name(): string
    {
        return 'get_current_datetime';
    }

    public function description(): string
    {
        return "Returns the current date and time in the server's timezone. Use this whenever the user asks something relative to \"today\", \"now\", or \"this week\".";
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass];
    }

    public function handle(Authenticatable $actor, array $arguments): array
    {
        return ['datetime' => now()->toIso8601String()];
    }
}
