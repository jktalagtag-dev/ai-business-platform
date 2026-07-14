<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InvalidAutomationJobRetryException extends DomainException
{
    public function __construct(string $currentStatus)
    {
        parent::__construct("Only a failed automation job can be retried (current status: \"{$currentStatus}\").");
    }
}
