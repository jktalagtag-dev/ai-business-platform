<?php

declare(strict_types=1);

use App\Application\Contracts\Services\AI\AiProviderInterface;
use Tests\Support\FakeAiProvider;

function fakeAiProvider(): FakeAiProvider
{
    $fake = new FakeAiProvider;
    app()->instance(AiProviderInterface::class, $fake);

    return $fake;
}

/**
 * Parses "event: x\ndata: {...}\n\n" frames into a list of [event, data].
 *
 * @return list<array{0: string, 1: array<string, mixed>}>
 */
function parseSseFrames(string $streamed): array
{
    $frames = [];

    foreach (explode("\n\n", trim($streamed)) as $rawFrame) {
        if (trim($rawFrame) === '') {
            continue;
        }

        $event = null;
        $data = null;

        foreach (explode("\n", $rawFrame) as $line) {
            if (str_starts_with($line, 'event:')) {
                $event = trim(substr($line, 6));
            } elseif (str_starts_with($line, 'data:')) {
                $data = json_decode(trim(substr($line, 5)), true);
            }
        }

        if ($event !== null) {
            $frames[] = [$event, $data ?? []];
        }
    }

    return $frames;
}

it('streams a plain assistant reply and persists both messages with token usage', function () {
    $fake = fakeAiProvider()->queueTextReply('The capital of France is Paris.', promptTokens: 42, completionTokens: 8);

    $token = ownerSession()['token'];
    $conversationId = asToken($token)->postJson('/api/v1/ai/conversations', [])->json('data.id');

    $response = asToken($token)->post("/api/v1/ai/conversations/{$conversationId}/messages", [
        'content' => 'What is the capital of France?',
    ]);

    $frames = parseSseFrames($response->streamedContent());
    $events = array_column($frames, 0);

    expect($events)->toContain('user_message', 'delta', 'message');

    $messageFrame = collect($frames)->firstWhere('0', 'message')[1];
    expect($messageFrame['content'])->toBe('The capital of France is Paris.');
    expect($messageFrame['usage'])->toBe(['prompt_tokens' => 42, 'completion_tokens' => 8]);

    $history = asToken($token)->getJson("/api/v1/ai/conversations/{$conversationId}/messages");
    $roles = collect($history->json('data'))->pluck('attributes.role')->all();
    expect($roles)->toBe(['user', 'assistant']);

    $conversation = asToken($token)->getJson("/api/v1/ai/conversations/{$conversationId}");
    $conversation->assertJsonPath('data.attributes.total_prompt_tokens', 42);
    $conversation->assertJsonPath('data.attributes.total_completion_tokens', 8);

    expect($fake->calls)->toHaveCount(1);
});

it('sends the conversation\'s system prompt as the first message to the provider', function () {
    $fake = fakeAiProvider()->queueTextReply('Ack.');

    $token = ownerSession()['token'];
    $conversationId = asToken($token)->postJson('/api/v1/ai/conversations', [
        'system_prompt' => 'You are a pirate. Speak like one.',
    ])->json('data.id');

    $response = asToken($token)->post("/api/v1/ai/conversations/{$conversationId}/messages", ['content' => 'Hello']);
    $response->assertOk();
    $response->streamedContent(); // forces the stream() closure — and thus the provider call — to run

    expect($fake->calls[0]['messages'][0])->toBe([
        'role' => 'system',
        'content' => 'You are a pirate. Speak like one.',
    ]);
});

it('carries prior turns as context memory on the next message', function () {
    $fake = fakeAiProvider()
        ->queueTextReply('First reply.')
        ->queueTextReply('Second reply.');

    $token = ownerSession()['token'];
    $conversationId = asToken($token)->postJson('/api/v1/ai/conversations', [])->json('data.id');

    $first = asToken($token)->post("/api/v1/ai/conversations/{$conversationId}/messages", ['content' => 'First message']);
    $first->assertOk();
    $first->streamedContent();

    $second = asToken($token)->post("/api/v1/ai/conversations/{$conversationId}/messages", ['content' => 'Second message']);
    $second->assertOk();
    $second->streamedContent();

    $secondCallMessages = $fake->calls[1]['messages'];
    $roles = array_column($secondCallMessages, 'role');

    // system, user (first), assistant (first reply), user (second) — the
    // whole prior exchange is included as context memory.
    expect($roles)->toBe(['system', 'user', 'assistant', 'user']);
    expect($secondCallMessages[1]['content'])->toBe('First message');
    expect($secondCallMessages[2]['content'])->toBe('First reply.');
    expect($secondCallMessages[3]['content'])->toBe('Second message');
});

it('executes a requested tool call and feeds the result back to the model', function () {
    $fake = fakeAiProvider();
    $fake->queueToolCall('call_1', 'get_current_datetime', []);
    $fake->queueTextReply('It is currently that time.');

    $token = ownerSession()['token'];
    $conversationId = asToken($token)->postJson('/api/v1/ai/conversations', [])->json('data.id');

    $response = asToken($token)->post("/api/v1/ai/conversations/{$conversationId}/messages", [
        'content' => 'What time is it?',
    ]);

    $frames = parseSseFrames($response->streamedContent());
    $events = array_column($frames, 0);

    expect($events)->toContain('tool_call', 'tool_result', 'message');

    $toolResultFrame = collect($frames)->firstWhere('0', 'tool_result')[1];
    expect($toolResultFrame['name'])->toBe('get_current_datetime');
    expect($toolResultFrame['result'])->toHaveKey('datetime');

    // The provider is called twice: once to get the tool_call request, once
    // more with the tool's result appended so the model can produce a final reply.
    expect($fake->calls)->toHaveCount(2);
    $toolMessageInSecondCall = collect($fake->calls[1]['messages'])->firstWhere('role', 'tool');
    expect($toolMessageInSecondCall['tool_call_id'])->toBe('call_1');
});

it('feeds back an error result when the model requests an unknown tool', function () {
    $fake = fakeAiProvider();
    $fake->queueToolCall('call_1', 'delete_all_tickets', []);
    $fake->queueTextReply('I could not do that.');

    $token = ownerSession()['token'];
    $conversationId = asToken($token)->postJson('/api/v1/ai/conversations', [])->json('data.id');

    $response = asToken($token)->post("/api/v1/ai/conversations/{$conversationId}/messages", [
        'content' => 'Delete everything',
    ]);

    $frames = parseSseFrames($response->streamedContent());
    $toolResultFrame = collect($frames)->firstWhere('0', 'tool_result')[1];

    expect($toolResultFrame['result'])->toHaveKey('error');
});

it('surfaces an SSE error event when the tool-call loop exceeds the iteration limit', function () {
    $fake = fakeAiProvider();

    for ($i = 0; $i < config('ai.max_tool_iterations') + 1; $i++) {
        $fake->queueToolCall("call_{$i}", 'get_current_datetime', []);
    }

    $token = ownerSession()['token'];
    $conversationId = asToken($token)->postJson('/api/v1/ai/conversations', [])->json('data.id');

    $response = asToken($token)->post("/api/v1/ai/conversations/{$conversationId}/messages", [
        'content' => 'Loop forever',
    ]);

    $frames = parseSseFrames($response->streamedContent());
    $events = array_column($frames, 0);

    expect($events)->toContain('error');
    expect($events)->not->toContain('message');
});

it('blocks sending a message into another user\'s conversation with a normal JSON 403 (not SSE)', function () {
    $session = ownerSession();
    $memberA = tokenForRoleWithUser($session['tenant_id'], 'Member', 'a@example.com');
    $memberB = tokenForRoleWithUser($session['tenant_id'], 'Member', 'b@example.com');

    $conversationId = asToken($memberA['token'])->postJson('/api/v1/ai/conversations', [])->json('data.id');

    $response = asToken($memberB['token'])->postJson("/api/v1/ai/conversations/{$conversationId}/messages", [
        'content' => 'Hi',
    ]);

    $response->assertStatus(403);
    $response->assertJsonPath('error.code', 'forbidden');
});

it('requires a non-empty message body', function () {
    $token = ownerSession()['token'];
    $conversationId = asToken($token)->postJson('/api/v1/ai/conversations', [])->json('data.id');

    asToken($token)->postJson("/api/v1/ai/conversations/{$conversationId}/messages", [])
        ->assertStatus(422);
});
