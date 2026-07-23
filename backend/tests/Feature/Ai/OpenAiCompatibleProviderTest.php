<?php

declare(strict_types=1);

use App\Infrastructure\AI\OpenAiCompatibleProvider;
use Illuminate\Support\Facades\Http;

function makeProvider(array $overrides = []): OpenAiCompatibleProvider
{
    $defaults = [
        'baseUrl' => 'https://openrouter.ai/api/v1',
        'apiKey' => 'chat-key',
        'model' => 'openai/gpt-4o',
        'embeddingBaseUrl' => 'https://api.openai.com/v1',
        'embeddingApiKey' => 'embedding-key',
        'embeddingModel' => 'text-embedding-3-small',
        'timeout' => 60,
        'siteUrl' => null,
        'siteName' => null,
    ];

    $args = array_merge($defaults, $overrides);

    return new OpenAiCompatibleProvider(...$args);
}

function sseBody(string $content): string
{
    $chunk = json_encode(['choices' => [['delta' => ['content' => $content]]]]);

    return "data: {$chunk}\n\ndata: [DONE]\n\n";
}

it('does not send attribution headers when site url/name are not configured', function () {
    Http::fake([
        'https://openrouter.ai/*' => Http::response(sseBody('hi'), 200),
    ]);

    makeProvider()->stream([], [], fn () => null);

    Http::assertSent(function ($request) {
        return ! $request->hasHeader('HTTP-Referer') && ! $request->hasHeader('X-Title');
    });
});

it('sends OpenRouter attribution headers when site url/name are configured', function () {
    Http::fake([
        'https://openrouter.ai/*' => Http::response(sseBody('hi'), 200),
    ]);

    makeProvider(['siteUrl' => 'https://example.com', 'siteName' => 'AI Business Platform'])
        ->stream([], [], fn () => null);

    Http::assertSent(function ($request) {
        return $request->header('HTTP-Referer') === ['https://example.com']
            && $request->header('X-Title') === ['AI Business Platform'];
    });
});

it('sends chat completions to the chat base url using the chat api key', function () {
    Http::fake([
        'https://openrouter.ai/*' => Http::response(sseBody('hi'), 200),
    ]);

    makeProvider()->stream([], [], fn () => null);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
            && $request->header('Authorization') === ['Bearer chat-key'];
    });
});

it('captures a Gemini extra_content.google.thought_signature from a tool_call delta', function () {
    // Shape captured live from Gemini's OpenAI-compat endpoint: the signature
    // arrives as a sibling of id/function on the tool_calls delta entry.
    $chunk = json_encode([
        'choices' => [[
            'delta' => [
                'tool_calls' => [[
                    'index' => 0,
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => ['name' => 'get_weather', 'arguments' => '{"city":"Paris"}'],
                    'extra_content' => ['google' => ['thought_signature' => 'opaque-signature']],
                ]],
            ],
        ]],
    ]);
    $body = "data: {$chunk}\n\ndata: [DONE]\n\n";

    Http::fake(['https://openrouter.ai/*' => Http::response($body, 200)]);

    $result = makeProvider()->stream([], [], fn () => null);

    expect($result->toolCalls)->toHaveCount(1);
    expect($result->toolCalls[0]->thoughtSignature)->toBe('opaque-signature');
});

it('leaves thoughtSignature null for a tool_call delta without extra_content', function () {
    $chunk = json_encode([
        'choices' => [[
            'delta' => [
                'tool_calls' => [[
                    'index' => 0,
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => ['name' => 'get_current_datetime', 'arguments' => '{}'],
                ]],
            ],
        ]],
    ]);
    $body = "data: {$chunk}\n\ndata: [DONE]\n\n";

    Http::fake(['https://openrouter.ai/*' => Http::response($body, 200)]);

    $result = makeProvider()->stream([], [], fn () => null);

    expect($result->toolCalls[0]->thoughtSignature)->toBeNull();
});

it('does not concatenate two tool calls together when the provider misreports index for both', function () {
    // Reproduces a real bug seen against Gemini's OpenAI-compat endpoint: two
    // distinct tool calls ("get_current_datetime" and "search_knowledge_base")
    // both arrived with index 0 (Gemini's compat layer doesn't reliably
    // increment it across calls), which used to concatenate their names into
    // one invalid "get_current_datetimesearch_knowledge_base" tool call that
    // the backend then rejected as unknown. Each call's id/name/arguments
    // arrive together in a single delta (Gemini doesn't stream function
    // calls character-by-character the way OpenAI does), which is what makes
    // the differing, already-populated id at the same index detectable.
    $firstCall = json_encode([
        'choices' => [[
            'delta' => [
                'tool_calls' => [[
                    'index' => 0,
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => ['name' => 'get_current_datetime', 'arguments' => '{}'],
                ]],
            ],
        ]],
    ]);
    $secondCall = json_encode([
        'choices' => [[
            'delta' => [
                'tool_calls' => [[
                    'index' => 0,
                    'id' => 'call_2',
                    'type' => 'function',
                    'function' => ['name' => 'search_knowledge_base', 'arguments' => '{"query":"leave"}'],
                ]],
            ],
        ]],
    ]);
    $body = "data: {$firstCall}\n\ndata: {$secondCall}\n\ndata: [DONE]\n\n";

    Http::fake(['https://openrouter.ai/*' => Http::response($body, 200)]);

    $result = makeProvider()->stream([], [], fn () => null);

    expect($result->toolCalls)->toHaveCount(2);
    expect($result->toolCalls[0]->id)->toBe('call_1');
    expect($result->toolCalls[0]->name)->toBe('get_current_datetime');
    expect($result->toolCalls[1]->id)->toBe('call_2');
    expect($result->toolCalls[1]->name)->toBe('search_knowledge_base');
});

it('still reassembles a single tool call whose name is split across multiple chunks at the same index', function () {
    // The normal OpenAI streaming shape this accumulation exists for: one
    // tool call's name/arguments arrive in fragments, id only on the first.
    $chunks = [
        ['index' => 0, 'id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'get_curr']],
        ['index' => 0, 'function' => ['name' => 'ent_weather']],
        ['index' => 0, 'function' => ['arguments' => '{"city":']],
        ['index' => 0, 'function' => ['arguments' => '"Paris"}']],
    ];
    $body = implode('', array_map(
        fn (array $delta) => 'data: '.json_encode(['choices' => [['delta' => ['tool_calls' => [$delta]]]]])."\n\n",
        $chunks
    )).'data: [DONE]'."\n\n";

    Http::fake(['https://openrouter.ai/*' => Http::response($body, 200)]);

    $result = makeProvider()->stream([], [], fn () => null);

    expect($result->toolCalls)->toHaveCount(1);
    expect($result->toolCalls[0]->name)->toBe('get_current_weather');
    expect($result->toolCalls[0]->argumentsJson)->toBe('{"city":"Paris"}');
});

it('sends embeddings to the embedding-specific base url using the embedding api key', function () {
    Http::fake([
        'https://api.openai.com/*' => Http::response(['data' => [
            ['index' => 0, 'embedding' => [0.1, 0.2]],
        ]], 200),
    ]);

    makeProvider()->embed(['hello world']);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.openai.com/v1/embeddings'
            && $request->header('Authorization') === ['Bearer embedding-key'];
    });
});
