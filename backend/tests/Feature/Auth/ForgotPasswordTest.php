<?php

declare(strict_types=1);

it('sends a reset link for a known email', function () {
    registerUser(['email' => 'ada@example.com']);

    $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ada@example.com']);

    $response->assertOk();
    $this->assertDatabaseHas('password_reset_tokens', ['email' => 'ada@example.com']);
});

it('returns the same generic response for an unknown email, revealing nothing', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

    $response->assertOk();
    $response->assertJsonPath(
        'data.message',
        'If an account exists for that email, a password reset link has been sent.'
    );
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'nobody@example.com']);
});

it('requires a valid email format', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email']);

    $response->assertStatus(422);
});
