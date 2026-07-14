<?php

declare(strict_types=1);

namespace App\Application\DTOs\Inventory;

final class CreateCategoryData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $parentCategoryId,
    ) {}
}
