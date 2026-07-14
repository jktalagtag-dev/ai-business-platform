<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rbac;

use App\Application\DTOs\Rbac\LoginData;
use App\Application\DTOs\Rbac\RegisterData;
use App\Application\DTOs\Rbac\ResetPasswordData;
use App\Application\Services\Rbac\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rbac\ForgotPasswordRequest;
use App\Http\Requests\Rbac\LoginRequest;
use App\Http\Requests\Rbac\RegisterRequest;
use App\Http\Requests\Rbac\ResetPasswordRequest;
use App\Http\Resources\Rbac\AuthResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Auth', description: 'Registration, login, logout, and password reset')]
final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    #[OAT\Post(
        path: '/api/v1/auth/register',
        tags: ['Auth'],
        summary: 'Register a new user and their tenant',
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation', 'tenant_name'],
                properties: [
                    new OAT\Property(property: 'name', type: 'string', example: 'Ada Lovelace'),
                    new OAT\Property(property: 'email', type: 'string', format: 'email', example: 'ada@example.com'),
                    new OAT\Property(property: 'password', type: 'string', format: 'password', example: 'Passw0rd!123'),
                    new OAT\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'Passw0rd!123'),
                    new OAT\Property(property: 'tenant_name', type: 'string', example: 'Analytical Engines Inc.'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Account and tenant created, token issued'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(new RegisterData(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            tenantName: $request->string('tenant_name')->toString(),
        ));

        return ApiResponse::success(new AuthResource($result), status: 201);
    }

    #[OAT\Post(
        path: '/api/v1/auth/login',
        tags: ['Auth'],
        summary: 'Authenticate and receive a Sanctum token',
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OAT\Property(property: 'email', type: 'string', format: 'email'),
                    new OAT\Property(property: 'password', type: 'string', format: 'password'),
                    new OAT\Property(
                        property: 'tenant_slug',
                        type: 'string',
                        nullable: true,
                        description: 'Required only when the account belongs to multiple tenants'
                    ),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Authenticated'),
            new OAT\Response(response: 409, description: 'Account belongs to multiple tenants, tenant_slug required'),
            new OAT\Response(response: 422, description: 'Invalid credentials'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(new LoginData(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            tenantSlug: $request->filled('tenant_slug') ? $request->string('tenant_slug')->toString() : null,
        ));

        return ApiResponse::success(new AuthResource($result));
    }

    #[OAT\Post(
        path: '/api/v1/auth/logout',
        tags: ['Auth'],
        summary: 'Revoke the current access token',
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Logged out'),
            new OAT\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        $this->authService->logout($request->user(), (string) $token->id);

        return ApiResponse::success(['message' => 'Logged out successfully.']);
    }

    #[OAT\Post(
        path: '/api/v1/auth/forgot-password',
        tags: ['Auth'],
        summary: 'Request a password reset link',
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['email'],
                properties: [new OAT\Property(property: 'email', type: 'string', format: 'email')]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Generic confirmation, regardless of whether the email exists'),
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->string('email')->toString());

        return ApiResponse::success([
            'message' => 'If an account exists for that email, a password reset link has been sent.',
        ]);
    }

    #[OAT\Post(
        path: '/api/v1/auth/reset-password',
        tags: ['Auth'],
        summary: 'Reset a password using a reset token',
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['email', 'token', 'password', 'password_confirmation'],
                properties: [
                    new OAT\Property(property: 'email', type: 'string', format: 'email'),
                    new OAT\Property(property: 'token', type: 'string'),
                    new OAT\Property(property: 'password', type: 'string', format: 'password'),
                    new OAT\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Password reset'),
            new OAT\Response(response: 422, description: 'Invalid or expired token'),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(new ResetPasswordData(
            email: $request->string('email')->toString(),
            token: $request->string('token')->toString(),
            password: $request->string('password')->toString(),
        ));

        return ApiResponse::success(['message' => 'Password has been reset successfully.']);
    }
}
