<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rbac;

use App\Application\Services\Rbac\RoleService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Rbac\RoleResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Roles', description: 'System roles available for assignment (Owner/Admin/Member-restricted)')]
final class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    #[OAT\Get(
        path: '/api/v1/roles',
        tags: ['Roles'],
        summary: 'List assignable system roles. Requires the owner or admin role.',
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Roles returned'),
            new OAT\Response(response: 403, description: 'Caller does not hold the owner or admin role'),
        ]
    )]
    public function index(): JsonResponse
    {
        return ApiResponse::success(RoleResource::collection($this->roleService->listAssignableRoles()));
    }
}
