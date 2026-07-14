<?php

declare(strict_types=1);

it('returns the authenticated user profile with tenant memberships', function () {
    $registered = registerUser(['email' => 'ada@example.com']);
    $token = $registered['data']['token'];

    $response = $this->withToken($token)->getJson('/api/v1/profile');

    $response->assertOk();
    $response->assertJsonPath('data.user.attributes.email', 'ada@example.com');
    $response->assertJsonCount(1, 'data.memberships');
    $response->assertJsonPath('data.memberships.0.attributes.role.name', 'Owner');
});

it('rejects unauthenticated profile access', function () {
    $response = $this->getJson('/api/v1/profile');

    $response->assertStatus(401);
    $response->assertJsonPath('error.code', 'unauthenticated');
});

it('updates the profile name and email', function () {
    $registered = registerUser(['email' => 'ada@example.com']);
    $token = $registered['data']['token'];

    $response = $this->withToken($token)->patchJson('/api/v1/profile', [
        'name' => 'Ada, Countess of Lovelace',
        'email' => 'countess@example.com',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.attributes.name', 'Ada, Countess of Lovelace');
    $response->assertJsonPath('data.attributes.email', 'countess@example.com');

    $this->assertDatabaseHas('users', ['email' => 'countess@example.com']);
});

it('rejects updating the profile email to one already taken by another user', function () {
    registerUser(['email' => 'taken@example.com']);
    $registered = registerUser(['email' => 'ada@example.com']);
    $token = $registered['data']['token'];

    $response = $this->withToken($token)->patchJson('/api/v1/profile', [
        'name' => 'Ada Lovelace',
        'email' => 'taken@example.com',
    ]);

    $response->assertStatus(422);
});
