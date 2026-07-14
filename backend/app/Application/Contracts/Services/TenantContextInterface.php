<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services;

interface TenantContextInterface
{
    public function tenantId(): string;
}
