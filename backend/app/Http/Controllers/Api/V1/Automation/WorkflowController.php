<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Automation;

use App\Application\Services\Automation\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Automation\StoreWorkflowRequest;
use App\Http\Resources\Automation\WorkflowResource;
use App\Http\Resources\Automation\WorkflowStepResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Automation', description: 'Event-driven and scheduled workflow automation')]
final class WorkflowController extends Controller
{
    public function __construct(private readonly WorkflowService $workflows) {}

    #[OAT\Get(
        path: '/api/v1/automation/workflows',
        tags: ['Automation'],
        summary: 'List workflows',
        security: [['sanctum' => []]],
        responses: [new OAT\Response(response: 200, description: 'Workflows returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = max(min((int) $request->query('per_page', 25), 100), 1);
        $paginator = $this->workflows->list($request->user(), $perPage);
        $items = collect($paginator->items())->map(fn ($w) => new WorkflowResource($w));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/automation/workflows',
        tags: ['Automation'],
        summary: 'Create a workflow (starts as draft — activate separately)',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name', 'steps'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                    new OAT\Property(
                        property: 'steps',
                        type: 'array',
                        description: 'Ordered: exactly one trigger step first, then any condition steps, then one or more action steps',
                        items: new OAT\Items(
                            properties: [
                                new OAT\Property(property: 'type', type: 'string', enum: ['trigger', 'condition', 'action']),
                                new OAT\Property(property: 'config', type: 'object'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Workflow created'),
            new OAT\Response(response: 422, description: 'Validation failed'),
            new OAT\Response(response: 403, description: 'Missing automation.manage permission'),
        ]
    )]
    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $workflow = $this->workflows->create(
            $request->user(),
            $request->string('name')->toString(),
            $request->input('description'),
            $request->input('steps'),
        );

        return ApiResponse::success(new WorkflowResource($workflow), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/automation/workflows/{workflow}',
        tags: ['Automation'],
        summary: 'Get a single workflow',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'workflow', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Workflow returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $workflow): JsonResponse
    {
        return ApiResponse::success(new WorkflowResource($this->workflows->find($request->user(), $workflow)));
    }

    #[OAT\Get(
        path: '/api/v1/automation/workflows/{workflow}/steps',
        tags: ['Automation'],
        summary: 'List a workflow\'s ordered steps',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'workflow', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Steps returned')]
    )]
    public function steps(Request $request, string $workflow): JsonResponse
    {
        $steps = $this->workflows->steps($request->user(), $workflow);

        return ApiResponse::success(WorkflowStepResource::collection($steps));
    }

    #[OAT\Post(
        path: '/api/v1/automation/workflows/{workflow}/activate',
        tags: ['Automation'],
        summary: 'Activate a workflow so it starts matching its trigger',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'workflow', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Workflow activated')]
    )]
    public function activate(Request $request, string $workflow): JsonResponse
    {
        return ApiResponse::success(new WorkflowResource($this->workflows->activate($request->user(), $workflow)));
    }

    #[OAT\Post(
        path: '/api/v1/automation/workflows/{workflow}/pause',
        tags: ['Automation'],
        summary: 'Pause a workflow so it stops matching its trigger',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'workflow', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Workflow paused')]
    )]
    public function pause(Request $request, string $workflow): JsonResponse
    {
        return ApiResponse::success(new WorkflowResource($this->workflows->pause($request->user(), $workflow)));
    }

    #[OAT\Delete(
        path: '/api/v1/automation/workflows/{workflow}',
        tags: ['Automation'],
        summary: 'Delete a workflow and its run history',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'workflow', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Deleted')]
    )]
    public function destroy(Request $request, string $workflow): JsonResponse
    {
        $this->workflows->delete($request->user(), $workflow);

        return ApiResponse::success(['message' => 'Workflow deleted.']);
    }
}
