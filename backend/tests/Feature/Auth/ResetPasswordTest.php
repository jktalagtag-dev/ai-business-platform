<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('resets the password with a valid token and allows login with the new password', function () {
    registerUser(['email' => 'ada@example.com', 'password' => 'OldPassw0rd!', 'password_confirmation' => 'OldPassw0rd!']);

    $user = User::where('email', 'ada@example.com')->firstOrFail();
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'ada@example.com',
        'token' => $token,
        'password' => 'NewPassw0rd!456',
        'password_confirmation' => 'NewPassw0rd!456',
    ]);

    $response->assertOk();

    $user->refresh();
    expect(Hash::check('NewPassw0rd!456', $user->password))->toBeTrue();

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'NewPassw0rd!456',
    ]);
    $login->assertOk();
});

it('rejects an invalid reset token', function () {
    registerUser(['email' => 'ada@example.com']);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'ada@example.com',
        'token' => 'not-a-real-token',
        'password' => 'NewPassw0rd!456',
        'password_confirmation' => 'NewPassw0rd!456',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
});

it('requires password confirmation to match', function () {
    registerUser(['email' => 'ada@example.com']);
    $user = User::where('email', 'ada@example.com')->firstOrFail();
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'ada@example.com',
        'token' => $token,
        'password' => 'NewPassw0rd!456',
        'password_confirmation' => 'Mismatch',
    ]);

    $response->assertStatus(422);
});
