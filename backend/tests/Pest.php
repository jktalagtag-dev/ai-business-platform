<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Infrastructure\Persistence\Eloquent\Models\Employee\Employee;
use App\Infrastructure\Persistence\Eloquent\Models\Role;
use App\Infrastructure\Persistence\Eloquent\Models\TenantUser;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolePermissionSeeder::class))
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Registers a user (which also provisions their tenant and Owner
 * membership) and returns the decoded JSON response body.
 *
 * @return array<string, mixed>
 */
function registerUser(array $overrides = []): array
{
    $payload = array_merge([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'Passw0rd!123',
        'password_confirmation' => 'Passw0rd!123',
        'tenant_name' => 'Analytical Engines Inc.',
    ], $overrides);

    $response = test()->postJson('/api/v1/auth/register', $payload);

    return $response->json();
}

/**
 * Registers a fresh Owner (and their tenant), returning both the bearer
 * token and the tenant id — the common starting point for Inventory tests.
 *
 * @return array{token: string, tenant_id: string}
 */
function ownerSession(array $overrides = []): array
{
    $registered = registerUser($overrides);

    return [
        'token' => $registered['data']['token'],
        'tenant_id' => $registered['data']['membership']['attributes']['tenant']['id'],
    ];
}

/**
 * Issues a token for a given role within an existing tenant, carrying the
 * same abilities AuthService would issue (role + tenant + permissions),
 * without going through a second full registration/login flow. Used to
 * exercise Policy enforcement (e.g. a Member blocked from managing
 * products) against the same tenant an Owner session already set up.
 */
function tokenForRole(string $tenantId, string $roleName, string $email): string
{
    $user = User::factory()->create(['email' => $email]);
    $role = Role::whereNull('tenant_id')
        ->where('name', $roleName)
        ->with('permissions')
        ->firstOrFail();

    TenantUser::create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    $abilities = array_merge(
        ['role:'.strtolower($roleName), 'tenant:'.$tenantId],
        $role->permissions->pluck('key')->all()
    );

    return $user->createToken('test-token', $abilities)->plainTextToken;
}

/**
 * Prepares an authenticated request for the given token. Sanctum's auth
 * guard caches the resolved user for the lifetime of the guard instance,
 * which persists across multiple simulated HTTP calls within a single test
 * method — so switching tokens mid-test (owner → member, tenant A → tenant
 * B) silently keeps resolving the *previous* token's user unless the guard
 * is reset first. Prefer this over test()->withToken() directly whenever a
 * test uses more than one identity.
 */
function asToken(string $token): TestCase
{
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

/**
 * Same as tokenForRole() but also returns the created platform user's id,
 * so a test can link an Employee record to it — used by self-service
 * profile tests and department-manager scoping tests.
 *
 * @return array{token: string, user_id: string}
 */
function tokenForRoleWithUser(string $tenantId, string $roleName, string $email): array
{
    $user = User::factory()->create(['email' => $email]);
    $role = Role::whereNull('tenant_id')->where('name', $roleName)->with('permissions')->firstOrFail();

    TenantUser::create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    $abilities = array_merge(
        ['role:'.strtolower($roleName), 'tenant:'.$tenantId],
        $role->permissions->pluck('key')->all()
    );

    return [
        'token' => $user->createToken('test-token', $abilities)->plainTextToken,
        'user_id' => $user->id,
    ];
}

/**
 * Creates an employee row directly via Eloquent (bypassing the API/Service),
 * for test setup — e.g. wiring up a manager or a self-linked employee before
 * exercising the endpoint under test.
 *
 * @param  array<string, mixed>  $overrides
 */
function createEmployeeRecord(string $tenantId, array $overrides = []): Employee
{
    return Employee::create(array_merge([
        'tenant_id' => $tenantId,
        'employee_number' => 'EMP-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
        'first_name' => 'Test',
        'last_name' => 'Employee',
        'employment_type' => 'full_time',
        'employment_status' => 'active',
        'hire_date' => now()->toDateString(),
    ], $overrides));
}
