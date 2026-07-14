<?php

declare(strict_types=1);

it('blocks a member from viewing audit logs', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $response = asToken($memberToken)->getJson('/api/v1/audit-logs');

    $response->assertStatus(403);
    $response->assertJsonPath('error.code', 'forbidden');
});

it('allows an admin to view audit logs', function () {
    $session = ownerSession();
    $adminToken = tokenForRole($session['tenant_id'], 'Admin', 'admin@example.com');

    asToken($session['token'])->postJson('/api/v1/categories', ['name' => 'Electronics'])->assertCreated();

    $response = asToken($adminToken)->getJson('/api/v1/audit-logs');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('only returns audit logs for the caller\'s own tenant', function () {
    $sessionA = ownerSession(['email' => 'a@example.com', 'tenant_name' => 'Tenant A']);
    asToken($sessionA['token'])->postJson('/api/v1/categories', ['name' => 'A Category'])->assertCreated();

    $sessionB = ownerSession(['email' => 'b@example.com', 'tenant_name' => 'Tenant B']);
    asToken($sessionB['token'])->postJson('/api/v1/categories', ['name' => 'B Category'])->assertCreated();

    $response = asToken($sessionB['token'])->getJson('/api/v1/audit-logs');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.attributes.subject_type', 'product_category');
});
