<?php

declare(strict_types=1);

use App\Domain\Ai\AiConversation;

function makeAiConversation(array $overrides = []): AiConversation
{
    return new AiConversation(
        id: $overrides['id'] ?? 'conv_01',
        tenantId: $overrides['tenantId'] ?? 'tenant_01',
        userId: $overrides['userId'] ?? 'user_01',
        title: $overrides['title'] ?? null,
        systemPrompt: $overrides['systemPrompt'] ?? null,
        provider: $overrides['provider'] ?? 'openai',
        model: $overrides['model'] ?? 'gpt-4o-mini',
        totalPromptTokens: $overrides['totalPromptTokens'] ?? 0,
        totalCompletionTokens: $overrides['totalCompletionTokens'] ?? 0,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('is owned by the user_id it was created with', function () {
    $conversation = makeAiConversation(['userId' => 'user_42']);

    expect($conversation->isOwnedBy('user_42'))->toBeTrue();
    expect($conversation->isOwnedBy('someone_else'))->toBeFalse();
});

it('falls back to the given default when no system prompt is set', function () {
    $conversation = makeAiConversation(['systemPrompt' => null]);

    expect($conversation->systemPromptOrDefault('You are helpful.'))->toBe('You are helpful.');
});

it('prefers its own system prompt over the default', function () {
    $conversation = makeAiConversation(['systemPrompt' => 'You are a pirate.']);

    expect($conversation->systemPromptOrDefault('You are helpful.'))->toBe('You are a pirate.');
});
