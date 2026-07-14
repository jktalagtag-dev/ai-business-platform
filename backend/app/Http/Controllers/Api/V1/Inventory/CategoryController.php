<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Application\DTOs\Inventory\CreateCategoryData;
use App\Application\DTOs\Inventory\UpdateCategoryData;
use App\Application\Services\Inventory\CategoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreCategoryRequest;
use App\Http\Requests\Inventory\UpdateCategoryRequest;
use App\Http\Resources\Inventory\CategoryResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Inventory / Categories', description: 'Product categories')]
final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    #[OAT\Get(
        path: '/api/v1/categories',
        tags: ['Inventory / Categories'],
        summary: 'List product categories',
        security: [['sanctum' => []]],
        responses: [new OAT\Response(response: 200, description: 'Categories returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->categories->list($request->user());
        $items = collect($paginator->items())->map(fn ($c) => new CategoryResource($c));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/categories',
        tags: ['Inventory / Categories'],
        summary: 'Create a product category',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string', example: 'Electronics'),
                    new OAT\Property(property: 'parent_category_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Category created'),
            new OAT\Response(response: 422, description: 'Validation failed'),
            new OAT\Response(response: 403, description: 'Missing categories.manage permission'),
        ]
    )]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->user(), new CreateCategoryData(
            name: $request->string('name')->toString(),
            parentCategoryId: $request->input('parent_category_id'),
        ));

        return ApiResponse::success(new CategoryResource($category), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/categories/{category}',
        tags: ['Inventory / Categories'],
        summary: 'Get a single category',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'category', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Category returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $category): JsonResponse
    {
        return ApiResponse::success(new CategoryResource($this->categories->find($request->user(), $category)));
    }

    #[OAT\Patch(
        path: '/api/v1/categories/{category}',
        tags: ['Inventory / Categories'],
        summary: 'Update a category',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'category', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string'),
                    new OAT\Property(property: 'parent_category_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Category updated'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdateCategoryRequest $request, string $category): JsonResponse
    {
        $updated = $this->categories->update($request->user(), $category, new UpdateCategoryData(
            name: $request->string('name')->toString(),
            parentCategoryId: $request->input('parent_category_id'),
        ));

        return ApiResponse::success(new CategoryResource($updated));
    }

    #[OAT\Delete(
        path: '/api/v1/categories/{category}',
        tags: ['Inventory / Categories'],
        summary: 'Delete a category',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'category', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Category deleted'),
            new OAT\Response(response: 403, description: 'Missing categories.manage permission'),
        ]
    )]
    public function destroy(Request $request, string $category): JsonResponse
    {
        $this->categories->delete($request->user(), $category);

        return ApiResponse::success(['message' => 'Category deleted.']);
    }
}
