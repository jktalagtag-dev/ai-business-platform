<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class AmbiguousTenantException extends DomainException
{
    /**
     * @param  list<array{slug: string, name: string}>  $availableTenants
     */
    public function __construct(public readonly array $availableTenants)
    {
        parent::__construct('This account belongs to multiple tenants; specify which one to sign in to.');
    }
}
