<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Employee;

use App\Application\DTOs\Employee\CreatePositionData;
use App\Application\DTOs\Employee\UpdatePositionData;
use App\Application\Services\Employee\PositionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StorePositionRequest;
use App\Http\Requests\Employee\UpdatePositionRequest;
use App\Http\Resources\Employee\PositionResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Employees / Positions', description: 'Job positions')]
final class PositionController extends Controller
{
    public function __construct(private readonly PositionService $positions) {}

    #[OAT\Get(
        path: '/api/v1/positions',
        tags: ['Employees / Positions'],
        summary: 'List positions',
        security: [['sanctum' => []]],
        responses: [new OAT\Response(response: 200, description: 'Positions returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->positions->list($request->user());
        $items = collect($paginator->items())->map(fn ($p) => new PositionResource($p));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/positions',
        tags: ['Employees / Positions'],
        summary: 'Create a position',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['title'],
                properties: [
                    new OAT\Property(property: 'title', type: 'string', example: 'Software Engineer'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Position created'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = $this->positions->create($request->user(), new CreatePositionData(
            title: $request->string('title')->toString(),
            description: $request->input('description'),
        ));

        return ApiResponse::success(new PositionResource($position), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/positions/{position}',
        tags: ['Employees / Positions'],
        summary: 'Get a single position',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'position', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Position returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $position): JsonResponse
    {
        return ApiResponse::success(new PositionResource($this->positions->find($request->user(), $position)));
    }

    #[OAT\Patch(
        path: '/api/v1/positions/{position}',
        tags: ['Employees / Positions'],
        summary: 'Update a position',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'position', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['title'],
                properties: [
                    new OAT\Property(property: 'title', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Position updated'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdatePositionRequest $request, string $position): JsonResponse
    {
        $updated = $this->positions->update($request->user(), $position, new UpdatePositionData(
            title: $request->string('title')->toString(),
            description: $request->input('description'),
        ));

        return ApiResponse::success(new PositionResource($updated));
    }

    #[OAT\Delete(
        path: '/api/v1/positions/{position}',
        tags: ['Employees / Positions'],
        summary: 'Delete a position',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'position', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Position deleted'),
            new OAT\Response(response: 403, description: 'Missing positions.manage permission'),
        ]
    )]
    public function destroy(Request $request, string $position): JsonResponse
    {
        $this->positions->delete($request->user(), $position);

        return ApiResponse::success(['message' => 'Position deleted.']);
    }
}
