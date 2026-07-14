<?php

declare(strict_types=1);

use App\Application\Contracts\Services\AI\AiToolInterface;
use App\Application\Services\AI\AiToolRegistry;
use Illuminate\Contracts\Auth\Authenticatable;

final class StubEchoTool implements AiToolInterface
{
    public function name(): string
    {
        return 'echo';
    }

    public function description(): string
    {
        return 'Echoes back the given text.';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => ['text' => ['type' => 'string']], 'required' => ['text']];
    }

    public function handle(Authenticatable $actor, array $arguments): array
    {
        return ['echoed' => $arguments['text']];
    }
}

final class StubFailingTool implements AiToolInterface
{
    public function name(): string
    {
        return 'always_fails';
    }

    public function description(): string
    {
        return 'Always throws.';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => new stdClass];
    }

    public function handle(Authenticatable $actor, array $arguments): array
    {
        throw new RuntimeException('boom');
    }
}

it('exposes each registered tool as an OpenAI-format function definition', function () {
    $registry = new AiToolRegistry([new StubEchoTool]);

    expect($registry->definitions())->toBe([
        [
            'type' => 'function',
            'function' => [
                'name' => 'echo',
                'description' => 'Echoes back the given text.',
                'parameters' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']], 'required' => ['text']],
            ],
        ],
    ]);
});

it('dispatches a call to the matching tool', function () {
    $registry = new AiToolRegistry([new StubEchoTool]);
    $actor = Mockery::mock(Authenticatable::class);

    $result = $registry->dispatch($actor, 'echo', ['text' => 'hello']);

    expect($result)->toBe(['echoed' => 'hello']);
});

it('returns an error result instead of throwing for an unknown tool name', function () {
    $registry = new AiToolRegistry([new StubEchoTool]);
    $actor = Mockery::mock(Authenticatable::class);

    $result = $registry->dispatch($actor, 'does_not_exist', []);

    expect($result)->toHaveKey('error');
});

it('returns an error result instead of throwing when a tool\'s handle() throws', function () {
    $registry = new AiToolRegistry([new StubFailingTool]);
    $actor = Mockery::mock(Authenticatable::class);

    $result = $registry->dispatch($actor, 'always_fails', []);

    expect($result)->toBe(['error' => 'boom']);
});
