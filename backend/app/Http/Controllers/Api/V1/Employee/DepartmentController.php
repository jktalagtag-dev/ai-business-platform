<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Employee;

use App\Application\DTOs\Employee\CreateDepartmentData;
use App\Application\DTOs\Employee\UpdateDepartmentData;
use App\Application\Services\Employee\DepartmentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreDepartmentRequest;
use App\Http\Requests\Employee\UpdateDepartmentRequest;
use App\Http\Resources\Employee\DepartmentResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Employees / Departments', description: 'Departments, optionally hierarchical')]
final class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departments) {}

    #[OAT\Get(
        path: '/api/v1/departments',
        tags: ['Employees / Departments'],
        summary: 'List departments',
        security: [['sanctum' => []]],
        responses: [new OAT\Response(response: 200, description: 'Departments returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->departments->list($request->user());
        $items = collect($paginator->items())->map(fn ($d) => new DepartmentResource($d));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/departments',
        tags: ['Employees / Departments'],
        summary: 'Create a department',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string', example: 'Engineering'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                    new OAT\Property(property: 'parent_department_id', type: 'string', nullable: true),
                    new OAT\Property(property: 'manager_employee_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Department created'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->departments->create($request->user(), new CreateDepartmentData(
            name: $request->string('name')->toString(),
            description: $request->input('description'),
            parentDepartmentId: $request->input('parent_department_id'),
            managerEmployeeId: $request->input('manager_employee_id'),
        ));

        return ApiResponse::success(new DepartmentResource($department), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/departments/{department}',
        tags: ['Employees / Departments'],
        summary: 'Get a single department',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'department', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Department returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $department): JsonResponse
    {
        return ApiResponse::success(new DepartmentResource($this->departments->find($request->user(), $department)));
    }

    #[OAT\Patch(
        path: '/api/v1/departments/{department}',
        tags: ['Employees / Departments'],
        summary: 'Update a department',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'department', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                    new OAT\Property(property: 'parent_department_id', type: 'string', nullable: true),
                    new OAT\Property(property: 'manager_employee_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Department updated'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdateDepartmentRequest $request, string $department): JsonResponse
    {
        $updated = $this->departments->update($request->user(), $department, new UpdateDepartmentData(
            name: $request->string('name')->toString(),
            description: $request->input('description'),
            parentDepartmentId: $request->input('parent_department_id'),
            managerEmployeeId: $request->input('manager_employee_id'),
        ));

        return ApiResponse::success(new DepartmentResource($updated));
    }

    #[OAT\Delete(
        path: '/api/v1/departments/{department}',
        tags: ['Employees / Departments'],
        summary: 'Delete a department',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'department', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Department deleted'),
            new OAT\Response(response: 403, description: 'Missing departments.manage permission'),
        ]
    )]
    public function destroy(Request $request, string $department): JsonResponse
    {
        $this->departments->delete($request->user(), $department);

        return ApiResponse::success(['message' => 'Department deleted.']);
    }
}
