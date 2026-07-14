<?php

declare(strict_types=1);

it('creates a conversation with configured defaults when no overrides are given', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/ai/conversations', []);

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.provider', 'openai');
    $response->assertJsonPath('data.attributes.model', config('ai.default_model'));
    $response->assertJsonPath('data.attributes.system_prompt', null);
    $response->assertJsonPath('data.attributes.total_prompt_tokens', 0);
    $response->assertJsonPath('data.attributes.total_completion_tokens', 0);
});

it('creates a conversation with a custom title, system prompt, and model', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/ai/conversations', [
        'title' => 'Deployment help',
        'system_prompt' => 'You are a DevOps expert.',
        'model' => 'gpt-4o',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.title', 'Deployment help');
    $response->assertJsonPath('data.attributes.system_prompt', 'You are a DevOps expert.');
    $response->assertJsonPath('data.attributes.model', 'gpt-4o');
});

it('lists only the caller\'s own conversations', function () {
    $session = ownerSession();
    $memberA = tokenForRoleWithUser($session['tenant_id'], 'Member', 'a@example.com');
    $memberB = tokenForRoleWithUser($session['tenant_id'], 'Member', 'b@example.com');

    asToken($memberA['token'])->postJson('/api/v1/ai/conversations', ['title' => 'A\'s chat'])->assertCreated();
    asToken($memberB['token'])->postJson('/api/v1/ai/conversations', ['title' => 'B\'s chat'])->assertCreated();

    $response = asToken($memberA['token'])->getJson('/api/v1/ai/conversations');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.attributes.title', 'A\'s chat');
});

it('blocks viewing another user\'s conversation', function () {
    $session = ownerSession();
    $memberA = tokenForRoleWithUser($session['tenant_id'], 'Member', 'a@example.com');
    $memberB = tokenForRoleWithUser($session['tenant_id'], 'Member', 'b@example.com');

    $conversationId = asToken($memberA['token'])->postJson('/api/v1/ai/conversations', [])->json('data.id');

    asToken($memberB['token'])->getJson("/api/v1/ai/conversations/{$conversationId}")->assertStatus(403);
});

it('returns 404 for a conversation that does not exist', function () {
    $token = ownerSession()['token'];

    asToken($token)->getJson('/api/v1/ai/conversations/01HZXXNOTAREALULIDXXXXXX01')->assertStatus(404);
});

it('deletes the caller\'s own conversation', function () {
    $token = ownerSession()['token'];
    $conversationId = asToken($token)->postJson('/api/v1/ai/conversations', [])->json('data.id');

    asToken($token)->deleteJson("/api/v1/ai/conversations/{$conversationId}")->assertOk();

    asToken($token)->getJson("/api/v1/ai/conversations/{$conversationId}")->assertStatus(404);
});

it('blocks deleting another user\'s conversation', function () {
    $session = ownerSession();
    $memberA = tokenForRoleWithUser($session['tenant_id'], 'Member', 'a@example.com');
    $memberB = tokenForRoleWithUser($session['tenant_id'], 'Member', 'b@example.com');

    $conversationId = asToken($memberA['token'])->postJson('/api/v1/ai/conversations', [])->json('data.id');

    asToken($memberB['token'])->deleteJson("/api/v1/ai/conversations/{$conversationId}")->assertStatus(403);
});
