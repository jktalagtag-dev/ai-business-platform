<?php

declare(strict_types=1);

namespace App\Application\Services\AI;

use App\Application\Contracts\Services\AI\AiToolInterface;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Collects every bound AiToolInterface (see AiServiceProvider) and exposes
 * them to ChatService as OpenAI-format tool definitions, dispatching calls
 * the model requests back to the matching implementation.
 */
final class AiToolRegistry
{
    /**
     * @var array<string, AiToolInterface>
     */
    private array $tools;

    /**
     * @param  list<AiToolInterface>  $tools
     */
    public function __construct(array $tools)
    {
        $this->tools = [];

        foreach ($tools as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function definitions(): array
    {
        return array_values(array_map(
            fn (AiToolInterface $tool): array => [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'parameters' => $tool->parameters(),
                ],
            ],
            $this->tools
        ));
    }

    /**
     * Never throws — an unknown tool name (the model hallucinating, or a
     * tool removed after the model was given its definition) is fed back
     * to the model as an error result so the conversation can recover,
     * rather than aborting the whole request.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function dispatch(Authenticatable $actor, string $name, array $arguments): array
    {
        $tool = $this->tools[$name] ?? null;

        if ($tool === null) {
            return ['error' => "Unknown tool: {$name}"];
        }

        try {
            return $tool->handle($actor, $arguments);
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
