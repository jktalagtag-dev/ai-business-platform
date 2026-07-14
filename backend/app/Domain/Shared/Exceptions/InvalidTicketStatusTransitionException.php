<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InvalidTicketStatusTransitionException extends DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Cannot transition a ticket from \"{$from}\" to \"{$to}\".");
    }
}
