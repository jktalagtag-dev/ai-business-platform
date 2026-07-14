<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\Services\TenantContextInterface;
use App\Http\Support\RequestTenantContext;
use Illuminate\Support\ServiceProvider;

final class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RequestTenantContext::class);
        $this->app->bind(
            TenantContextInterface::class,
            static fn ($app) => $app->make(RequestTenantContext::class)
        );
    }
}
