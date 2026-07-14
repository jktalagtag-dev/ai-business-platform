<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Application\DTOs\Inventory\CreateSupplierData;
use App\Application\DTOs\Inventory\UpdateSupplierData;
use App\Application\Services\Inventory\SupplierService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreSupplierRequest;
use App\Http\Requests\Inventory\UpdateSupplierRequest;
use App\Http\Resources\Inventory\SupplierResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Inventory / Suppliers', description: 'Suppliers')]
final class SupplierController extends Controller
{
    public function __construct(private readonly SupplierService $suppliers) {}

    #[OAT\Get(
        path: '/api/v1/suppliers',
        tags: ['Inventory / Suppliers'],
        summary: 'List suppliers',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'status', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'search', in: 'query', schema: new OAT\Schema(type: 'string')),
        ],
        responses: [new OAT\Response(response: 200, description: 'Suppliers returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ], fn ($v) => $v !== null);

        $paginator = $this->suppliers->list($request->user(), $filters);
        $items = collect($paginator->items())->map(fn ($s) => new SupplierResource($s));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/suppliers',
        tags: ['Inventory / Suppliers'],
        summary: 'Create a supplier',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string', example: 'Acme Supplies Ltd.'),
                    new OAT\Property(property: 'contact_email', type: 'string', format: 'email', nullable: true),
                    new OAT\Property(property: 'contact_phone', type: 'string', nullable: true),
                    new OAT\Property(property: 'address', type: 'object', nullable: true),
                    new OAT\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Supplier created'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->suppliers->create($request->user(), new CreateSupplierData(
            name: $request->string('name')->toString(),
            contactEmail: $request->input('contact_email'),
            contactPhone: $request->input('contact_phone'),
            address: $request->input('address'),
            status: $request->input('status', 'active'),
        ));

        return ApiResponse::success(new SupplierResource($supplier), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/suppliers/{supplier}',
        tags: ['Inventory / Suppliers'],
        summary: 'Get a single supplier',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'supplier', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Supplier returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $supplier): JsonResponse
    {
        return ApiResponse::success(new SupplierResource($this->suppliers->find($request->user(), $supplier)));
    }

    #[OAT\Patch(
        path: '/api/v1/suppliers/{supplier}',
        tags: ['Inventory / Suppliers'],
        summary: 'Update a supplier',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'supplier', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string'),
                    new OAT\Property(property: 'contact_email', type: 'string', format: 'email', nullable: true),
                    new OAT\Property(property: 'contact_phone', type: 'string', nullable: true),
                    new OAT\Property(property: 'address', type: 'object', nullable: true),
                    new OAT\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Supplier updated'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdateSupplierRequest $request, string $supplier): JsonResponse
    {
        $updated = $this->suppliers->update($request->user(), $supplier, new UpdateSupplierData(
            name: $request->string('name')->toString(),
            contactEmail: $request->input('contact_email'),
            contactPhone: $request->input('contact_phone'),
            address: $request->input('address'),
            status: $request->input('status', 'active'),
        ));

        return ApiResponse::success(new SupplierResource($updated));
    }

    #[OAT\Delete(
        path: '/api/v1/suppliers/{supplier}',
        tags: ['Inventory / Suppliers'],
        summary: 'Delete a supplier',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'supplier', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Supplier deleted'),
            new OAT\Response(response: 403, description: 'Missing suppliers.manage permission'),
        ]
    )]
    public function destroy(Request $request, string $supplier): JsonResponse
    {
        $this->suppliers->delete($request->user(), $supplier);

        return ApiResponse::success(['message' => 'Supplier deleted.']);
    }
}
