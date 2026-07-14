<?php

declare(strict_types=1);

namespace App\Application\DTOs\Inventory;

final class UpdateSupplierData
{
    /**
     * @param  array<string, mixed>|null  $address
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $contactEmail,
        public readonly ?string $contactPhone,
        public readonly ?array $address,
        public readonly string $status,
    ) {}
}
