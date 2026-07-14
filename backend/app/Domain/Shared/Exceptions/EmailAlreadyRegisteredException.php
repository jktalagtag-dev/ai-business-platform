<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class EmailAlreadyRegisteredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('An account with this email address already exists.');
    }
}
