<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\Repositories\RoleRepositoryInterface;
use App\Application\Contracts\Repositories\TenantRepositoryInterface;
use App\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use App\Application\Contracts\Repositories\UserRepositoryInterface;
use App\Application\Contracts\Services\PasswordResetterInterface;
use App\Application\Contracts\Services\TokenIssuerInterface;
use App\Infrastructure\Auth\LaravelPasswordResetter;
use App\Infrastructure\Auth\SanctumTokenIssuer;
use App\Infrastructure\Persistence\Eloquent\Repositories\RoleRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\TenantRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\TenantUserRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\UserRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

final class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(TenantUserRepositoryInterface::class, TenantUserRepository::class);
        $this->app->bind(TokenIssuerInterface::class, SanctumTokenIssuer::class);
        $this->app->bind(PasswordResetterInterface::class, LaravelPasswordResetter::class);
    }

    public function boot(): void
    {
        // This is an API-only backend with no "password.reset" web route, so
        // the reset link must point at the SPA frontend (FRONTEND.md), which
        // reads the token/email query params and calls POST /v1/auth/reset-password.
        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return rtrim(config('app.frontend_url'), '/')."/reset-password?token={$token}&email={$email}";
        });
    }
}
