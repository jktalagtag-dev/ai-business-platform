<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Automation;

use App\Application\Services\Automation\AutomationJobService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Automation\AutomationJobResource;
use App\Http\Resources\Automation\AutomationJobStepResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Automation')]
final class AutomationJobController extends Controller
{
    public function __construct(private readonly AutomationJobService $jobs) {}

    #[OAT\Get(
        path: '/api/v1/automation/jobs',
        tags: ['Automation'],
        summary: 'List workflow run history (execution instances)',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'workflow_id', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'status', in: 'query', description: 'queued|running|succeeded|failed', schema: new OAT\Schema(type: 'string')),
        ],
        responses: [new OAT\Response(response: 200, description: 'Jobs returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'workflow_id' => $request->query('workflow_id'),
            'status' => $request->query('status'),
        ], fn ($v) => $v !== null);

        $perPage = max(min((int) $request->query('per_page', 25), 100), 1);
        $paginator = $this->jobs->list($request->user(), $filters, $perPage);
        $items = collect($paginator->items())->map(fn ($j) => new AutomationJobResource($j));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Get(
        path: '/api/v1/automation/jobs/{job}',
        tags: ['Automation'],
        summary: 'Get a single job run',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'job', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Job returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $job): JsonResponse
    {
        return ApiResponse::success(new AutomationJobResource($this->jobs->find($request->user(), $job)));
    }

    #[OAT\Get(
        path: '/api/v1/automation/jobs/{job}/steps',
        tags: ['Automation'],
        summary: 'Per-step audit trail for one job run',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'job', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Steps returned')]
    )]
    public function steps(Request $request, string $job): JsonResponse
    {
        $perPage = max(min((int) $request->query('per_page', 50), 200), 1);
        $paginator = $this->jobs->steps($request->user(), $job, $perPage);
        $items = collect($paginator->items())->map(fn ($s) => new AutomationJobStepResource($s));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/automation/jobs/{job}/retry',
        tags: ['Automation'],
        summary: 'Retry a failed job (resets attempts and re-dispatches)',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'job', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Job re-queued'),
            new OAT\Response(response: 400, description: 'Job is not in a retryable (failed) state'),
            new OAT\Response(response: 403, description: 'Missing automation.manage permission'),
        ]
    )]
    public function retry(Request $request, string $job): JsonResponse
    {
        return ApiResponse::success(new AutomationJobResource($this->jobs->retry($request->user(), $job)));
    }
}
