<?php

declare(strict_types=1);
use App\Infrastructure\Persistence\Eloquent\Models\Role;
use App\Infrastructure\Persistence\Eloquent\Models\Tenant;
use App\Infrastructure\Persistence\Eloquent\Models\TenantUser;
use App\Infrastructure\Persistence\Eloquent\Models\User;

it('logs in with valid credentials and receives a token', function () {
    registerUser(['email' => 'ada@example.com', 'password' => 'Passw0rd!123', 'password_confirmation' => 'Passw0rd!123']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'Passw0rd!123',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.user.attributes.email', 'ada@example.com');
    $response->assertJsonPath('data.membership.attributes.role.name', 'Owner');
    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
});

it('rejects an unknown email', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'whatever123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
});

it('rejects an incorrect password', function () {
    registerUser(['email' => 'ada@example.com', 'password' => 'Passw0rd!123', 'password_confirmation' => 'Passw0rd!123']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'WrongPassword!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
});

it('requires a tenant_slug when the account belongs to multiple tenants', function () {
    $first = registerUser(['email' => 'ada@example.com', 'tenant_name' => 'First Co']);
    $token = $first['data']['token'];

    // Register a second tenant owned by a different user, then invite/attach
    // the same user is out of scope for this auth-only slice, so we instead
    // simulate a second membership directly to exercise the disambiguation path.
    $secondTenant = Tenant::create([
        'name' => 'Second Co',
        'slug' => 'second-co',
        'plan' => 'free',
    ]);
    $ownerRole = Role::whereNull('tenant_id')->where('name', 'Owner')->firstOrFail();
    $user = User::where('email', 'ada@example.com')->firstOrFail();

    TenantUser::create([
        'tenant_id' => $secondTenant->id,
        'user_id' => $user->id,
        'role_id' => $ownerRole->id,
        'status' => 'active',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'Passw0rd!123',
    ]);

    $response->assertStatus(409);
    $response->assertJsonPath('error.code', 'conflict');
    expect($response->json('error.available_tenants'))->toHaveCount(2);

    $disambiguated = $this->postJson('/api/v1/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'Passw0rd!123',
        'tenant_slug' => 'second-co',
    ]);

    $disambiguated->assertOk();
    $disambiguated->assertJsonPath('data.membership.attributes.tenant.slug', 'second-co');
});
