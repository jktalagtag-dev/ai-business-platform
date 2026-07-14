<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Application\DTOs\Inventory\AdjustStockData;
use App\Application\Services\Inventory\InventoryItemService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AdjustStockRequest;
use App\Http\Resources\Inventory\InventoryItemResource;
use App\Http\Resources\Inventory\InventoryMovementResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Inventory / Stock', description: 'Stock levels and movement ledger, one record per product')]
final class StockController extends Controller
{
    public function __construct(private readonly InventoryItemService $inventoryItems) {}

    #[OAT\Get(
        path: '/api/v1/stock',
        tags: ['Inventory / Stock'],
        summary: 'List stock levels',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'low_stock', in: 'query', description: 'Only items at or below their reorder point', schema: new OAT\Schema(type: 'boolean')),
        ],
        responses: [new OAT\Response(response: 200, description: 'Stock levels returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->boolean('low_stock') ? ['low_stock' => true] : [];

        $paginator = $this->inventoryItems->list($request->user(), $filters);
        $items = collect($paginator->items())->map(fn ($i) => new InventoryItemResource($i));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Get(
        path: '/api/v1/stock/{product}',
        tags: ['Inventory / Stock'],
        summary: "Get one product's stock level",
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'product', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Stock level returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $product): JsonResponse
    {
        $item = $this->inventoryItems->findByProductId($request->user(), $product);

        return ApiResponse::success(new InventoryItemResource($item));
    }

    #[OAT\Post(
        path: '/api/v1/stock/{product}/adjust',
        tags: ['Inventory / Stock'],
        summary: 'Adjust stock quantity, recording a movement in the ledger',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'product', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['quantity', 'movement_type'],
                properties: [
                    new OAT\Property(property: 'quantity', type: 'integer', description: 'Signed delta: positive for inbound, negative for outbound', example: 50),
                    new OAT\Property(property: 'movement_type', type: 'string', enum: ['inbound', 'outbound', 'adjustment']),
                    new OAT\Property(property: 'reason', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Stock adjusted'),
            new OAT\Response(response: 422, description: 'Validation failed or insufficient stock'),
            new OAT\Response(response: 403, description: 'Missing inventory.manage permission'),
        ]
    )]
    public function adjust(AdjustStockRequest $request, string $product): JsonResponse
    {
        $item = $this->inventoryItems->adjust($request->user(), $product, new AdjustStockData(
            quantity: (int) $request->input('quantity'),
            movementType: $request->string('movement_type')->toString(),
            reason: $request->input('reason'),
        ));

        return ApiResponse::success(new InventoryItemResource($item));
    }

    #[OAT\Get(
        path: '/api/v1/stock/{product}/movements',
        tags: ['Inventory / Stock'],
        summary: "List a product's stock movement ledger",
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'product', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Movements returned')]
    )]
    public function movements(Request $request, string $product): JsonResponse
    {
        $paginator = $this->inventoryItems->movements($request->user(), $product);
        $items = collect($paginator->items())->map(fn ($m) => new InventoryMovementResource($m));

        return ApiResponse::paginated($items, $paginator);
    }
}
