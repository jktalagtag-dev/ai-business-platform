<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

final class Supplier
{
    /**
     * @param  array<string, mixed>|null  $address
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly ?string $contactEmail,
        public readonly ?string $contactPhone,
        public readonly ?array $address,
        public readonly string $status,
    ) {}
}
