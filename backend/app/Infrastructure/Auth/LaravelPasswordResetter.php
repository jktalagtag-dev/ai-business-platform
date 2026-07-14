<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Application\Contracts\Services\PasswordResetterInterface;
use App\Domain\Shared\Exceptions\PasswordResetFailedException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class LaravelPasswordResetter implements PasswordResetterInterface
{
    public function sendResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    public function reset(string $email, string $token, string $password): void
    {
        $status = Password::reset(
            ['email' => $email, 'token' => $token, 'password' => $password],
            function ($user) use ($password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new PasswordResetFailedException(__($status));
        }
    }
}
