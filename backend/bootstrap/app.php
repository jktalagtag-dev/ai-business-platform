<?php

use App\Domain\Shared\Exceptions\AiProviderException;
use App\Domain\Shared\Exceptions\AmbiguousTenantException;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\Exceptions\EmailAlreadyRegisteredException;
use App\Domain\Shared\Exceptions\InsufficientStockException;
use App\Domain\Shared\Exceptions\InvalidCredentialsException;
use App\Domain\Shared\Exceptions\InvalidTechnicianAssignmentException;
use App\Domain\Shared\Exceptions\PasswordResetFailedException;
use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ResolveTenant;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            AttachRequestId::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'tenant' => ResolveTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (ValidationException $e, Request $request) {
            $details = collect($e->errors())
                ->flatMap(fn (array $messages, string $field) => collect($messages)->map(
                    fn (string $message) => ['field' => $field, 'message' => $message]
                ))
                ->values()
                ->all();

            return ApiResponse::error('validation_failed', 'The given data was invalid.', $details, 422);
        });

        $exceptions->render(fn (AuthenticationException $e, Request $request) => ApiResponse::error(
            'unauthenticated', 'Authentication required.', status: 401
        ));

        // Laravel's base Handler always converts AuthorizationException (thrown by
        // Gate::authorize()) into AccessDeniedHttpException before custom render()
        // callbacks are checked, so that's the type to match here, not the former.
        $exceptions->render(fn (AccessDeniedHttpException $e, Request $request) => ApiResponse::error(
            'forbidden', $e->getMessage() ?: 'You are not authorized to perform this action.', status: 403
        ));

        $exceptions->render(fn (ModelNotFoundException|NotFoundHttpException $e, Request $request) => ApiResponse::error(
            'not_found', 'The requested resource could not be found.', status: 404
        ));

        $exceptions->render(fn (InvalidCredentialsException $e, Request $request) => ApiResponse::error(
            'validation_failed',
            $e->getMessage(),
            [['field' => 'email', 'message' => $e->getMessage()]],
            422
        ));

        $exceptions->render(fn (EmailAlreadyRegisteredException $e, Request $request) => ApiResponse::error(
            'validation_failed',
            $e->getMessage(),
            [['field' => 'email', 'message' => $e->getMessage()]],
            422
        ));

        $exceptions->render(fn (PasswordResetFailedException $e, Request $request) => ApiResponse::error(
            'validation_failed',
            $e->getMessage(),
            [['field' => 'token', 'message' => $e->getMessage()]],
            422
        ));

        $exceptions->render(fn (AmbiguousTenantException $e, Request $request) => ApiResponse::error(
            'conflict',
            $e->getMessage(),
            status: 409,
            context: ['available_tenants' => $e->availableTenants]
        ));

        $exceptions->render(fn (InsufficientStockException $e, Request $request) => ApiResponse::error(
            'validation_failed',
            $e->getMessage(),
            [['field' => 'quantity', 'message' => $e->getMessage()]],
            422
        ));

        $exceptions->render(fn (InvalidTechnicianAssignmentException $e, Request $request) => ApiResponse::error(
            'validation_failed',
            $e->getMessage(),
            [['field' => 'technician_employee_id', 'message' => $e->getMessage()]],
            422
        ));

        // Registered before the generic DomainException handler below —
        // AiProviderException extends it, and Laravel's renderer resolution
        // uses the first matching registered callback, so the more specific
        // type must come first to get its own 502 instead of a 400.
        $exceptions->render(fn (AiProviderException $e, Request $request) => ApiResponse::error(
            'ai_provider_error', $e->getMessage(), status: 502
        ));

        $exceptions->render(fn (DomainException $e, Request $request) => ApiResponse::error(
            'bad_request', $e->getMessage(), status: 400
        ));
    })->create();
