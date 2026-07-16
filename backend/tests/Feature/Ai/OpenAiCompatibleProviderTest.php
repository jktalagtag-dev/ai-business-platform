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
