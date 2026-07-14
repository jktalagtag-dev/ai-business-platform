<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\KnowledgeBase;

use App\Application\Services\KnowledgeBase\KnowledgeBaseAnswerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeBase\AskKnowledgeBaseRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Knowledge Base')]
final class AskController extends Controller
{
    public function __construct(private readonly KnowledgeBaseAnswerService $answers) {}

    #[OAT\Post(
        path: '/api/v1/knowledge-base/ask',
        tags: ['Knowledge Base'],
        summary: 'Ask a question answered from the knowledge base, with citations',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['query'],
                properties: [
                    new OAT\Property(property: 'query', type: 'string'),
                    new OAT\Property(property: 'top_k', type: 'integer', nullable: true, description: 'Defaults to config(knowledge_base.top_k)'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Answer with citations returned'),
            new OAT\Response(response: 403, description: 'Missing knowledge_base.view permission'),
        ]
    )]
    public function store(AskKnowledgeBaseRequest $request): JsonResponse
    {
        $result = $this->answers->ask(
            $request->user(),
            $request->string('query')->toString(),
            $request->integer('top_k') ?: null,
        );

        return ApiResponse::success([
            'answer' => $result['answer'],
            'citations' => $result['citations'],
            'usage' => [
                'prompt_tokens' => $result['prompt_tokens'],
                'completion_tokens' => $result['completion_tokens'],
            ],
        ]);
    }
}
