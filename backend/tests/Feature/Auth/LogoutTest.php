<?php

declare(strict_types=1);

it('revokes the current token on logout', function () {
    $registered = registerUser();
    $token = $registered['data']['token'];

    $me = $this->withToken($token)->getJson('/api/v1/profile');
    $me->assertOk();

    $logout = $this->withToken($token)->postJson('/api/v1/auth/logout');
    $logout->assertOk();

    // Sanctum's auth guard caches the resolved user for the lifetime of the
    // guard instance; within a single test the container (and therefore the
    // guard) persists across simulated requests, so it must be reset here to
    // observe the same re-resolution a real second HTTP request would do.
    $this->app['auth']->forgetGuards();

    $after = $this->withToken($token)->getJson('/api/v1/profile');
    $after->assertStatus(401);
});

it('rejects logout without a token', function () {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertStatus(401);
    $response->assertJsonPath('error.code', 'unauthenticated');
});
