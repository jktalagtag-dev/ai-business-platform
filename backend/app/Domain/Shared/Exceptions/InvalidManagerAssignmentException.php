<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InvalidManagerAssignmentException extends DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
