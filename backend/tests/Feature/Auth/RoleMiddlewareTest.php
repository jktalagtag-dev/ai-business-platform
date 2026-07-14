<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\Role;
use App\Infrastructure\Persistence\Eloquent\Models\Tenant;
use App\Infrastructure\Persistence\Eloquent\Models\TenantUser;
use App\Infrastructure\Persistence\Eloquent\Models\User;

it('allows an owner to list roles', function () {
    $registered = registerUser();
    $token = $registered['data']['token'];

    $response = $this->withToken($token)->getJson('/api/v1/roles');

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('attributes.name');
    expect($names->all())->toContain('Owner', 'Admin', 'Member');
});

it('blocks a member from listing roles', function () {
    $user = User::factory()->create(['email' => 'member@example.com']);
    $tenant = Tenant::create(['name' => 'Member Co', 'slug' => 'member-co', 'plan' => 'free']);
    $memberRole = Role::whereNull('tenant_id')->where('name', 'Member')->firstOrFail();

    TenantUser::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $memberRole->id,
        'status' => 'active',
    ]);

    $token = $user->createToken('test-token', ['role:member', 'profile.view', 'profile.update'])->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/roles');

    $response->assertStatus(403);
    $response->assertJsonPath('error.code', 'forbidden');
});

it('allows a member to view their own profile despite lacking the owner/admin role', function () {
    $user = User::factory()->create(['email' => 'member2@example.com']);
    $tenant = Tenant::create(['name' => 'Member Co 2', 'slug' => 'member-co-2', 'plan' => 'free']);
    $memberRole = Role::whereNull('tenant_id')->where('name', 'Member')->firstOrFail();

    TenantUser::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $memberRole->id,
        'status' => 'active',
    ]);

    $token = $user->createToken('test-token', ['role:member', 'profile.view', 'profile.update'])->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/profile');

    $response->assertOk();
    $response->assertJsonPath('data.memberships.0.attributes.role.name', 'Member');
});
