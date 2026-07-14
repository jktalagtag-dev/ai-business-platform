<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InsufficientStockException extends DomainException
{
    public function __construct(string $sku, int $available, int $requested)
    {
        parent::__construct(
            "Cannot remove {$requested} unit(s) of \"{$sku}\" — only {$available} unit(s) available."
        );
    }
}
