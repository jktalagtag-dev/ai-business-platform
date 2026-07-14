<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ai;

use App\Application\Contracts\Repositories\Ai\AiMessageRepositoryInterface;
use App\Application\DTOs\Ai\CreateConversationData;
use App\Application\Services\AI\ConversationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\CreateAiConversationRequest;
use App\Http\Resources\Ai\AiConversationResource;
use App\Http\Resources\Ai\AiMessageResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'AI Assistant', description: 'AI conversations, message history, and chat')]
final class AiConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly AiMessageRepositoryInterface $messages,
    ) {}

    #[OAT\Get(
        path: '/api/v1/ai/conversations',
        tags: ['AI Assistant'],
        summary: "List the caller's own AI conversations",
        security: [['sanctum' => []]],
        responses: [new OAT\Response(response: 200, description: 'Conversations returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = max(min((int) $request->query('per_page', 25), 100), 1);
        $paginator = $this->conversations->list($request->user(), $perPage);
        $items = collect($paginator->items())->map(fn ($c) => new AiConversationResource($c));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/ai/conversations',
        tags: ['AI Assistant'],
        summary: 'Start a new AI conversation',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            content: new OAT\JsonContent(properties: [
                new OAT\Property(property: 'title', type: 'string', nullable: true),
                new OAT\Property(property: 'system_prompt', type: 'string', nullable: true, description: 'Falls back to the configured default when omitted'),
                new OAT\Property(property: 'model', type: 'string', nullable: true, description: 'Falls back to the configured default model when omitted'),
            ])
        ),
        responses: [new OAT\Response(response: 201, description: 'Conversation created')]
    )]
    public function store(CreateAiConversationRequest $request): JsonResponse
    {
        $conversation = $this->conversations->create($request->user(), new CreateConversationData(
            title: $request->input('title'),
            systemPrompt: $request->input('system_prompt'),
            model: $request->input('model'),
        ));

        return ApiResponse::success(new AiConversationResource($conversation), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/ai/conversations/{conversation}',
        tags: ['AI Assistant'],
        summary: 'Get a single conversation',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'conversation', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Conversation returned'),
            new OAT\Response(response: 403, description: 'Not the owner'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $conversation): JsonResponse
    {
        return ApiResponse::success(new AiConversationResource($this->conversations->find($request->user(), $conversation)));
    }

    #[OAT\Get(
        path: '/api/v1/ai/conversations/{conversation}/messages',
        tags: ['AI Assistant'],
        summary: 'List message history for a conversation (conversation history)',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'conversation', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Messages returned')]
    )]
    public function messages(Request $request, string $conversation): JsonResponse
    {
        // Authorizes ownership before touching message history.
        $this->conversations->find($request->user(), $conversation);

        $perPage = max(min((int) $request->query('per_page', 50), 200), 1);
        $paginator = $this->messages->paginateForConversation($conversation, $perPage);
        $items = collect($paginator->items())->map(fn ($m) => new AiMessageResource($m));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Delete(
        path: '/api/v1/ai/conversations/{conversation}',
        tags: ['AI Assistant'],
        summary: 'Delete a conversation and its message history',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'conversation', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Deleted')]
    )]
    public function destroy(Request $request, string $conversation): JsonResponse
    {
        $this->conversations->delete($request->user(), $conversation);

        return ApiResponse::success(['message' => 'Conversation deleted.']);
    }
}
