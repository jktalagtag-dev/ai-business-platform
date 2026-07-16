<?php

declare(strict_types=1);

use App\Domain\Ai\AiMessage;
use App\Domain\Ai\ToolCall;

function makeAiMessage(array $overrides = []): AiMessage
{
    return new AiMessage(
        id: $overrides['id'] ?? 'msg_01',
        tenantId: $overrides['tenantId'] ?? 'tenant_01',
        conversationId: $overrides['conversationId'] ?? 'conv_01',
        role: $overrides['role'] ?? 'user',
        content: array_key_exists('content', $overrides) ? $overrides['content'] : 'Hello',
        toolCalls: $overrides['toolCalls'] ?? null,
        toolCallId: $overrides['toolCallId'] ?? null,
        name: $overrides['name'] ?? null,
        promptTokens: $overrides['promptTokens'] ?? null,
        completionTokens: $overrides['completionTokens'] ?? null,
        createdAt: new DateTimeImmutable,
    );
}

it('renders a plain user message in provider format', function () {
    $message = makeAiMessage(['role' => 'user', 'content' => 'Hi there']);

    expect($message->toProviderFormat())->toBe(['role' => 'user', 'content' => 'Hi there']);
});

it('renders an assistant tool-call message without a content key when content is null', function () {
    $message = makeAiMessage([
        'role' => 'assistant',
        'content' => null,
        'toolCalls' => [new ToolCall('call_1', 'get_current_datetime', '{}')],
    ]);

    $rendered = $message->toProviderFormat();

    expect($rendered)->not->toHaveKey('content');
    expect($rendered['tool_calls'])->toBe([
        ['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'get_current_datetime', 'arguments' => '{}']],
    ]);
});

it('renders a tool-role message with tool_call_id and name', function () {
    $message = makeAiMessage([
        'role' => 'tool',
        'content' => '{"datetime":"2026-01-01T00:00:00+00:00"}',
        'toolCallId' => 'call_1',
        'name' => 'get_current_datetime',
    ]);

    expect($message->toProviderFormat())->toBe([
        'role' => 'tool',
        'content' => '{"datetime":"2026-01-01T00:00:00+00:00"}',
        'tool_call_id' => 'call_1',
        'name' => 'get_current_datetime',
    ]);
});

it('decodes a tool call\'s JSON arguments into an array', function () {
    $call = new ToolCall('call_1', 'search_employees', '{"query":"grace"}');

    expect($call->argumentsArray())->toBe(['query' => 'grace']);
});

it('includes extra_content.google.thought_signature on a tool call that has one', function () {
    $message = makeAiMessage([
        'role' => 'assistant',
        'content' => null,
        'toolCalls' => [new ToolCall('call_1', 'get_current_datetime', '{}', 'opaque-signature')],
    ]);

    expect($message->toProviderFormat()['tool_calls'])->toBe([
        [
            'id' => 'call_1',
            'type' => 'function',
            'function' => ['name' => 'get_current_datetime', 'arguments' => '{}'],
            'extra_content' => ['google' => ['thought_signature' => 'opaque-signature']],
        ],
    ]);
});

it('omits extra_content entirely for a tool call without a thought signature', function () {
    $message = makeAiMessage([
        'role' => 'assistant',
        'content' => null,
        'toolCalls' => [new ToolCall('call_1', 'get_current_datetime', '{}')],
    ]);

    expect($message->toProviderFormat()['tool_calls'][0])->not->toHaveKey('extra_content');
});
