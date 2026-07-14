<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class AiToolIterationLimitExceededException extends DomainException
{
    public function __construct(int $limit)
    {
        parent::__construct("The assistant requested more than {$limit} tool calls in a row without producing a final reply.");
    }
}
