<?php

declare(strict_types=1);

namespace App\Infrastructure\AI;

use App\Application\Contracts\Services\AI\AiProviderInterface;
use App\Application\DTOs\Ai\AiCompletionResult;
use App\Domain\Ai\ToolCall;
use App\Domain\Shared\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;

/**
 * Talks to any endpoint implementing the OpenAI Chat Completions streaming
 * wire format (`POST {base_url}/chat/completions`, `text/event-stream`
 * body of `data: {json}\n\n` frames terminated by `data: [DONE]`). Real
 * OpenAI by default; base_url/api_key/model are the only config needed to
 * point this at a different OpenAI-compatible endpoint instead.
 */
final class OpenAiCompatibleProvider implements AiProviderInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $embeddingBaseUrl,
        private readonly string $embeddingApiKey,
        private readonly string $embeddingModel,
        private readonly int $timeout,
        private readonly ?string $siteUrl = null,
        private readonly ?string $siteName = null,
    ) {}

    public function stream(array $messages, array $tools, callable $onDelta): AiCompletionResult
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => true,
            'stream_options' => ['include_usage' => true],
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
        }

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->withHeaders(array_filter([
                'HTTP-Referer' => $this->siteUrl,
                'X-Title' => $this->siteName,
            ]))
            ->withOptions(['stream' => true])
            ->post(rtrim($this->baseUrl, '/').'/chat/completions', $payload);

        if ($response->failed()) {
            throw new AiProviderException(
                "AI provider request failed with status {$response->status()}: {$response->body()}"
            );
        }

        return $this->consumeStream($response->toPsrResponse()->getBody(), $onDelta);
    }

    public function embed(array $texts): array
    {
        $response = Http::withToken($this->embeddingApiKey)
            ->timeout($this->timeout)
            ->post(rtrim($this->embeddingBaseUrl, '/').'/embeddings', [
                'model' => $this->embeddingModel,
                'input' => $texts,
            ]);

        if ($response->failed()) {
            throw new AiProviderException(
                "AI provider embeddings request failed with status {$response->status()}: {$response->body()}"
            );
        }

        return collect($response->json('data'))
            ->sortBy('index')
            ->pluck('embedding')
            ->values()
            ->all();
    }

    /**
     * @param  callable(string):void  $onDelta
     */
    private function consumeStream(StreamInterface $body, callable $onDelta): AiCompletionResult
    {
        $content = null;
        $toolCallFragments = [];
        $promptTokens = null;
        $completionTokens = null;
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($eventEnd = strpos($buffer, "\n\n")) !== false) {
                $rawEvent = substr($buffer, 0, $eventEnd);
                $buffer = substr($buffer, $eventEnd + 2);

                foreach (explode("\n", $rawEvent) as $line) {
                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $data = trim(substr($line, 5));

                    if ($data === '[DONE]') {
                        continue 2;
                    }

                    $chunk = json_decode($data, true);

                    if (! is_array($chunk)) {
                        continue;
                    }

                    if (isset($chunk['usage']['prompt_tokens'])) {
                        $promptTokens = $chunk['usage']['prompt_tokens'];
                        $completionTokens = $chunk['usage']['completion_tokens'] ?? null;
                    }

                    $delta = $chunk['choices'][0]['delta'] ?? null;

                    if ($delta === null) {
                        continue;
                    }

                    if (isset($delta['content']) && $delta['content'] !== '') {
                        $content ??= '';
                        $content .= $delta['content'];
                        $onDelta($delta['content']);
                    }

                    foreach ($delta['tool_calls'] ?? [] as $toolCallDelta) {
                        $index = $toolCallDelta['index'] ?? 0;
                        $toolCallFragments[$index] ??= ['id' => '', 'name' => '', 'arguments' => '', 'thought_signature' => null];

                        if (isset($toolCallDelta['id'])) {
                            $toolCallFragments[$index]['id'] = $toolCallDelta['id'];
                        }

                        if (isset($toolCallDelta['function']['name'])) {
                            $toolCallFragments[$index]['name'] .= $toolCallDelta['function']['name'];
                        }

                        if (isset($toolCallDelta['function']['arguments'])) {
                            $toolCallFragments[$index]['arguments'] .= $toolCallDelta['function']['arguments'];
                        }

                        // Gemini-specific: required to validate this tool call if it's
                        // replayed in a later request (see ToolCall::$thoughtSignature).
                        if (isset($toolCallDelta['extra_content']['google']['thought_signature'])) {
                            $toolCallFragments[$index]['thought_signature'] = $toolCallDelta['extra_content']['google']['thought_signature'];
                        }
                    }
                }
            }
        }

        $toolCalls = array_values(array_map(
            fn (array $fragment): ToolCall => new ToolCall(
                id: $fragment['id'],
                name: $fragment['name'],
                argumentsJson: $fragment['arguments'] !== '' ? $fragment['arguments'] : '{}',
                thoughtSignature: $fragment['thought_signature'],
            ),
            $toolCallFragments
        ));

        return new AiCompletionResult($content, $toolCalls, $promptTokens, $completionTokens);
    }
}
