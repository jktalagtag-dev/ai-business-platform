<?php

declare(strict_types=1);

namespace App\Http\Support;

use App\Application\Contracts\Services\TenantContextInterface;
use RuntimeException;

/**
 * Holds the tenant a request is scoped to. Populated once per request by
 * ResolveTenant middleware and read by Services/Repositories via
 * TenantContextInterface. Bound as a scoped (per-request) singleton so every
 * class resolves the same instance within one request lifecycle.
 */
final class RequestTenantContext implements TenantContextInterface
{
    private ?string $tenantId = null;

    public function setTenantId(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function tenantId(): string
    {
        if ($this->tenantId === null) {
            throw new RuntimeException('No tenant context has been resolved for this request.');
        }

        return $this->tenantId;
    }
}
