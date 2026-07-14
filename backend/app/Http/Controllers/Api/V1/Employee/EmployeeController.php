<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Employee;

use App\Application\DTOs\Employee\CreateEmployeeData;
use App\Application\DTOs\Employee\UpdateEmployeeData;
use App\Application\Services\Employee\EmployeeService;
use App\Domain\Employee\EmergencyContact;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\UploadEmployeeAvatarRequest;
use App\Http\Resources\Employee\EmployeeResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Employees', description: 'Employee directory, profiles, and lifecycle')]
final class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employees) {}

    #[OAT\Get(
        path: '/api/v1/employees',
        tags: ['Employees'],
        summary: 'List employees — full directory for Owner/Admin/HR, department-scoped for managers',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'department_id', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'position_id', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'employment_status', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'manager_employee_id', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'search', in: 'query', description: 'Matches name, email, or employee number', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'sort', in: 'query', schema: new OAT\Schema(type: 'string', enum: ['first_name', 'last_name', 'hire_date', 'created_at'])),
            new OAT\Parameter(name: 'direction', in: 'query', schema: new OAT\Schema(type: 'string', enum: ['asc', 'desc'])),
        ],
        responses: [new OAT\Response(response: 200, description: 'Employees returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'department_id' => $request->query('department_id'),
            'position_id' => $request->query('position_id'),
            'employment_status' => $request->query('employment_status'),
            'manager_employee_id' => $request->query('manager_employee_id'),
            'search' => $request->query('search'),
            'sort' => $request->query('sort'),
            'direction' => $request->query('direction'),
        ], fn ($v) => $v !== null);

        $perPage = min((int) $request->query('per_page', 25), 100);
        $paginator = $this->employees->list($request->user(), $filters, max($perPage, 1));
        $items = collect($paginator->items())->map(fn ($e) => new EmployeeResource($e));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/employees',
        tags: ['Employees'],
        summary: 'Create an employee (system-generates the employee number). Requires Admin, HR, or Owner.',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['first_name', 'last_name', 'hire_date'],
                properties: [
                    new OAT\Property(property: 'user_id', type: 'string', nullable: true),
                    new OAT\Property(property: 'first_name', type: 'string'),
                    new OAT\Property(property: 'last_name', type: 'string'),
                    new OAT\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OAT\Property(property: 'phone', type: 'string', nullable: true),
                    new OAT\Property(property: 'department_id', type: 'string', nullable: true),
                    new OAT\Property(property: 'position_id', type: 'string', nullable: true),
                    new OAT\Property(property: 'manager_employee_id', type: 'string', nullable: true),
                    new OAT\Property(property: 'employment_type', type: 'string', enum: ['full_time', 'part_time', 'contractor', 'intern']),
                    new OAT\Property(property: 'employment_status', type: 'string', enum: ['active', 'on_leave', 'suspended', 'terminated']),
                    new OAT\Property(property: 'hire_date', type: 'string', format: 'date'),
                    new OAT\Property(property: 'address', type: 'object', nullable: true),
                    new OAT\Property(property: 'emergency_contact', type: 'object', nullable: true),
                    new OAT\Property(property: 'bio', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Employee created'),
            new OAT\Response(response: 422, description: 'Validation failed'),
            new OAT\Response(response: 403, description: 'Missing employees.manage permission'),
        ]
    )]
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employees->create($request->user(), new CreateEmployeeData(
            userId: $request->input('user_id'),
            firstName: $request->string('first_name')->toString(),
            lastName: $request->string('last_name')->toString(),
            email: $request->input('email'),
            phone: $request->input('phone'),
            departmentId: $request->input('department_id'),
            positionId: $request->input('position_id'),
            managerEmployeeId: $request->input('manager_employee_id'),
            employmentType: $request->input('employment_type', 'full_time'),
            employmentStatus: $request->input('employment_status', 'active'),
            hireDate: $request->string('hire_date')->toString(),
            address: $request->input('address'),
            emergencyContact: $this->emergencyContactFromRequest($request),
            bio: $request->input('bio'),
        ));

        return ApiResponse::success(new EmployeeResource($employee), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/employees/me',
        tags: ['Employees'],
        summary: "Get the authenticated user's own employee profile",
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Profile returned'),
            new OAT\Response(response: 404, description: 'No employee record is linked to this account'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new EmployeeResource($this->employees->findSelf($request->user())));
    }

    #[OAT\Get(
        path: '/api/v1/employees/{employee}',
        tags: ['Employees'],
        summary: 'Get a single employee',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'employee', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Employee returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $employee): JsonResponse
    {
        return ApiResponse::success(new EmployeeResource($this->employees->find($request->user(), $employee)));
    }

    #[OAT\Patch(
        path: '/api/v1/employees/{employee}',
        tags: ['Employees'],
        summary: 'Update an employee. Employees may update their own contact/profile fields; only Admin/HR/Owner may change department, position, manager, status, or dates.',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'employee', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Employee updated'),
            new OAT\Response(response: 422, description: 'Validation failed'),
            new OAT\Response(response: 403, description: 'Not authorized to change one or more of the submitted fields'),
        ]
    )]
    public function update(UpdateEmployeeRequest $request, string $employee): JsonResponse
    {
        $updated = $this->employees->update($request->user(), $employee, new UpdateEmployeeData(
            firstName: $request->string('first_name')->toString(),
            lastName: $request->string('last_name')->toString(),
            email: $request->input('email'),
            phone: $request->input('phone'),
            departmentId: $request->input('department_id'),
            positionId: $request->input('position_id'),
            managerEmployeeId: $request->input('manager_employee_id'),
            employmentType: $request->input('employment_type', 'full_time'),
            employmentStatus: $request->input('employment_status', 'active'),
            hireDate: $request->string('hire_date')->toString(),
            terminationDate: $request->input('termination_date'),
            address: $request->input('address'),
            emergencyContact: $this->emergencyContactFromRequest($request),
            bio: $request->input('bio'),
        ));

        return ApiResponse::success(new EmployeeResource($updated));
    }

    #[OAT\Post(
        path: '/api/v1/employees/{employee}/avatar',
        tags: ['Employees'],
        summary: 'Upload a profile picture',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'employee', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OAT\Schema(
                    required: ['avatar'],
                    properties: [new OAT\Property(property: 'avatar', type: 'string', format: 'binary')]
                )
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Avatar uploaded'),
            new OAT\Response(response: 422, description: 'Validation failed (not an image, or too large)'),
        ]
    )]
    public function uploadAvatar(UploadEmployeeAvatarRequest $request, string $employee): JsonResponse
    {
        $path = $request->file('avatar')->store('employee-avatars', 'public');

        $updated = $this->employees->uploadAvatar($request->user(), $employee, $path);

        return ApiResponse::success(new EmployeeResource($updated));
    }

    #[OAT\Delete(
        path: '/api/v1/employees/{employee}',
        tags: ['Employees'],
        summary: 'Archive (soft-delete) an employee',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'employee', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Employee archived'),
            new OAT\Response(response: 403, description: 'Missing employees.manage permission'),
        ]
    )]
    public function destroy(Request $request, string $employee): JsonResponse
    {
        $this->employees->delete($request->user(), $employee);

        return ApiResponse::success(['message' => 'Employee archived.']);
    }

    private function emergencyContactFromRequest(Request $request): ?EmergencyContact
    {
        if (! $request->filled('emergency_contact')) {
            return null;
        }

        return EmergencyContact::fromArray($request->input('emergency_contact'));
    }
}
