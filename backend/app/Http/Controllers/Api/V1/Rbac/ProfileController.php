<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rbac;

use App\Application\DTOs\Rbac\UpdateProfileData;
use App\Application\Services\Rbac\ProfileService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rbac\UpdateProfileRequest;
use App\Http\Resources\Rbac\ProfileResource;
use App\Http\Resources\Rbac\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Profile', description: "The authenticated user's own profile")]
final class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    #[OAT\Get(
        path: '/api/v1/profile',
        tags: ['Profile'],
        summary: "Get the authenticated user's profile and tenant memberships",
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Profile returned'),
            new OAT\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new ProfileResource($this->profileService->show($request->user())));
    }

    #[OAT\Patch(
        path: '/api/v1/profile',
        tags: ['Profile'],
        summary: "Update the authenticated user's name and email",
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name', 'email'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string'),
                    new OAT\Property(property: 'email', type: 'string', format: 'email'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Profile updated'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->update($request->user(), new UpdateProfileData(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
        ));

        return ApiResponse::success(new UserResource($user));
    }
}
