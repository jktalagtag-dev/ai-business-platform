<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services;

use App\Domain\Shared\Exceptions\PasswordResetFailedException;

interface PasswordResetterInterface
{
    public function sendResetLink(string $email): void;

    /**
     * @throws PasswordResetFailedException
     */
    public function reset(string $email, string $token, string $password): void;
}
