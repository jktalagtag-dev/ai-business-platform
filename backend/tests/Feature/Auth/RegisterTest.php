<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\Tenant;
use App\Infrastructure\Persistence\Eloquent\Models\TenantUser;
use App\Infrastructure\Persistence\Eloquent\Models\User;

it('registers a new user and provisions a tenant with the Owner role', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'Passw0rd!123',
        'password_confirmation' => 'Passw0rd!123',
        'tenant_name' => 'Analytical Engines Inc.',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.user.attributes.email', 'ada@example.com');
    $response->assertJsonPath('data.membership.attributes.role.name', 'Owner');
    $response->assertJsonPath('data.membership.attributes.tenant.name', 'Analytical Engines Inc.');
    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    $this->assertDatabaseCount('tenants', 1);
    $this->assertDatabaseCount('tenant_users', 1);

    $user = User::where('email', 'ada@example.com')->firstOrFail();
    $tenant = Tenant::firstOrFail();
    $this->assertDatabaseHas('tenant_users', [
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
    ]);
});

it('rejects registration with a duplicate email', function () {
    registerUser(['email' => 'duplicate@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Someone Else',
        'email' => 'duplicate@example.com',
        'password' => 'Passw0rd!123',
        'password_confirmation' => 'Passw0rd!123',
        'tenant_name' => 'Another Co.',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
});

it('requires name, email, password, and tenant_name', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');

    $fields = collect($response->json('error.details'))->pluck('field')->unique()->values()->all();

    expect($fields)->toContain('name', 'email', 'password', 'tenant_name');
});

it('rejects a password confirmation mismatch', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada2@example.com',
        'password' => 'Passw0rd!123',
        'password_confirmation' => 'DoesNotMatch',
        'tenant_name' => 'Analytical Engines Inc.',
    ]);

    $response->assertStatus(422);
});

it('generates unique tenant slugs when tenant names collide', function () {
    registerUser(['email' => 'first@example.com', 'tenant_name' => 'Acme']);
    registerUser(['email' => 'second@example.com', 'tenant_name' => 'Acme']);

    $slugs = TenantUser::with('tenant')->get()->pluck('tenant.slug')->sort()->values();

    expect($slugs->all())->toBe(['acme', 'acme-2']);
});
