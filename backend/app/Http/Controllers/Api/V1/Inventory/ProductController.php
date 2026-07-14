<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Application\DTOs\Inventory\CreateProductData;
use App\Application\DTOs\Inventory\UpdateProductData;
use App\Application\Services\Inventory\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Http\Resources\Inventory\ProductResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Inventory / Products', description: 'Products catalogue')]
final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    #[OAT\Get(
        path: '/api/v1/products',
        tags: ['Inventory / Products'],
        summary: 'List products',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'category_id', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'is_active', in: 'query', schema: new OAT\Schema(type: 'boolean')),
            new OAT\Parameter(name: 'search', in: 'query', schema: new OAT\Schema(type: 'string')),
        ],
        responses: [new OAT\Response(response: 200, description: 'Products returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'category_id' => $request->query('category_id'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
            'search' => $request->query('search'),
        ], fn ($v) => $v !== null);

        $paginator = $this->products->list($request->user(), $filters);
        $items = collect($paginator->items())->map(fn ($p) => new ProductResource($p));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/products',
        tags: ['Inventory / Products'],
        summary: 'Create a product (also provisions its stock record)',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['sku', 'name', 'unit_price', 'cost_price'],
                properties: [
                    new OAT\Property(property: 'sku', type: 'string', example: 'WIDGET-001'),
                    new OAT\Property(property: 'name', type: 'string', example: 'Widget'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                    new OAT\Property(property: 'category_id', type: 'string', nullable: true),
                    new OAT\Property(property: 'unit_price', type: 'number', format: 'float', example: 19.99),
                    new OAT\Property(property: 'cost_price', type: 'number', format: 'float', example: 9.5),
                    new OAT\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Product created'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->user(), new CreateProductData(
            sku: $request->string('sku')->toString(),
            name: $request->string('name')->toString(),
            description: $request->input('description'),
            categoryId: $request->input('category_id'),
            unitPrice: (string) $request->input('unit_price'),
            costPrice: (string) $request->input('cost_price'),
            isActive: $request->boolean('is_active', true),
        ));

        return ApiResponse::success(new ProductResource($product), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/products/{product}',
        tags: ['Inventory / Products'],
        summary: 'Get a single product',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'product', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Product returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $product): JsonResponse
    {
        return ApiResponse::success(new ProductResource($this->products->find($request->user(), $product)));
    }

    #[OAT\Patch(
        path: '/api/v1/products/{product}',
        tags: ['Inventory / Products'],
        summary: 'Update a product',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'product', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['sku', 'name', 'unit_price', 'cost_price'],
                properties: [
                    new OAT\Property(property: 'sku', type: 'string'),
                    new OAT\Property(property: 'name', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                    new OAT\Property(property: 'category_id', type: 'string', nullable: true),
                    new OAT\Property(property: 'unit_price', type: 'number', format: 'float'),
                    new OAT\Property(property: 'cost_price', type: 'number', format: 'float'),
                    new OAT\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Product updated'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdateProductRequest $request, string $product): JsonResponse
    {
        $updated = $this->products->update($request->user(), $product, new UpdateProductData(
            sku: $request->string('sku')->toString(),
            name: $request->string('name')->toString(),
            description: $request->input('description'),
            categoryId: $request->input('category_id'),
            unitPrice: (string) $request->input('unit_price'),
            costPrice: (string) $request->input('cost_price'),
            isActive: $request->boolean('is_active', true),
        ));

        return ApiResponse::success(new ProductResource($updated));
    }

    #[OAT\Delete(
        path: '/api/v1/products/{product}',
        tags: ['Inventory / Products'],
        summary: 'Delete a product',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'product', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Product deleted'),
            new OAT\Response(response: 403, description: 'Missing products.manage permission'),
        ]
    )]
    public function destroy(Request $request, string $product): JsonResponse
    {
        $this->products->delete($request->user(), $product);

        return ApiResponse::success(['message' => 'Product deleted.']);
    }
}
