<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ai;

use App\Application\Services\AI\ChatService;
use App\Application\Services\AI\ConversationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\SendAiMessageRequest;
use OpenApi\Attributes as OAT;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[OAT\Tag(name: 'AI Assistant')]
final class AiChatController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly ChatService $chat,
    ) {}

    /**
     * Ownership is authorized (via ConversationService::find(), which the
     * caller relies on for 'view') and the conversation is loaded *before*
     * the streamed response begins, so an invalid/foreign conversation id
     * still produces a normal JSON 403/404 — only failures that occur
     * mid-completion (e.g. the upstream AI provider erroring) surface as an
     * `event: error` frame instead, since headers are already committed by
     * that point.
     */
    #[OAT\Post(
        path: '/api/v1/ai/conversations/{conversation}/messages',
        tags: ['AI Assistant'],
        summary: 'Send a message and stream the assistant\'s reply (text/event-stream)',
        description: 'SSE frames: `user_message`, `delta` (incremental content), `tool_call`, `tool_result`, `message` (final content + token usage), `error`.',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'conversation', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(required: ['content'], properties: [new OAT\Property(property: 'content', type: 'string')])
        ),
        responses: [
            new OAT\Response(response: 200, description: 'text/event-stream of chat events'),
            new OAT\Response(response: 403, description: 'Not the owner of this conversation'),
        ]
    )]
    public function store(SendAiMessageRequest $request, string $conversation): StreamedResponse
    {
        $actor = $request->user();
        $conversationEntity = $this->conversations->find($actor, $conversation);
        $content = $request->string('content')->toString();

        return response()->stream(function () use ($actor, $conversationEntity, $content) {
            $emit = static function (string $event, array $data): void {
                echo "event: {$event}\n";
                echo 'data: '.json_encode($data)."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            };

            try {
                $this->chat->reply($actor, $conversationEntity, $content, $emit);
            } catch (\Throwable $e) {
                $emit('error', ['message' => $e->getMessage()]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
