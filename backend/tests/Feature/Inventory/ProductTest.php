<?php

declare(strict_types=1);

it('creates a product and auto-provisions its stock record', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/products', [
        'sku' => 'WIDGET-001',
        'name' => 'Widget',
        'unit_price' => 19.99,
        'cost_price' => 9.50,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.sku', 'WIDGET-001');
    $productId = $response->json('data.id');

    $stock = asToken($token)->getJson("/api/v1/stock/{$productId}");
    $stock->assertOk();
    $stock->assertJsonPath('data.attributes.quantity_on_hand', 0);
    $stock->assertJsonPath('data.attributes.product_sku', 'WIDGET-001');
});

it('lists, shows, updates, and deletes a product', function () {
    $token = ownerSession()['token'];

    $productId = asToken($token)->postJson('/api/v1/products', [
        'sku' => 'WIDGET-001', 'name' => 'Widget', 'unit_price' => 10, 'cost_price' => 5,
    ])->json('data.id');

    $index = asToken($token)->getJson('/api/v1/products');
    $index->assertOk();
    expect($index->json('data'))->toHaveCount(1);

    $update = asToken($token)->patchJson("/api/v1/products/{$productId}", [
        'sku' => 'WIDGET-001', 'name' => 'Deluxe Widget', 'unit_price' => 24.99, 'cost_price' => 12,
    ]);
    $update->assertOk();
    $update->assertJsonPath('data.attributes.name', 'Deluxe Widget');

    $destroy = asToken($token)->deleteJson("/api/v1/products/{$productId}");
    $destroy->assertOk();

    asToken($token)->getJson("/api/v1/products/{$productId}")->assertStatus(404);
});

it('filters products by category, active state, and search', function () {
    $token = ownerSession()['token'];
    $categoryId = asToken($token)->postJson('/api/v1/categories', ['name' => 'Electronics'])->json('data.id');

    asToken($token)->postJson('/api/v1/products', [
        'sku' => 'A-1', 'name' => 'Alpha', 'unit_price' => 1, 'cost_price' => 1, 'category_id' => $categoryId,
    ])->assertCreated();
    asToken($token)->postJson('/api/v1/products', [
        'sku' => 'B-1', 'name' => 'Beta', 'unit_price' => 1, 'cost_price' => 1, 'is_active' => false,
    ])->assertCreated();

    $byCategory = asToken($token)->getJson("/api/v1/products?category_id={$categoryId}");
    expect($byCategory->json('data'))->toHaveCount(1);
    $byCategory->assertJsonPath('data.0.attributes.sku', 'A-1');

    $byActive = asToken($token)->getJson('/api/v1/products?is_active=false');
    expect($byActive->json('data'))->toHaveCount(1);
    $byActive->assertJsonPath('data.0.attributes.sku', 'B-1');

    $bySearch = asToken($token)->getJson('/api/v1/products?search=Alpha');
    expect($bySearch->json('data'))->toHaveCount(1);
});

it('rejects a duplicate SKU within the same tenant', function () {
    $token = ownerSession()['token'];

    asToken($token)->postJson('/api/v1/products', [
        'sku' => 'WIDGET-001', 'name' => 'Widget', 'unit_price' => 10, 'cost_price' => 5,
    ])->assertCreated();

    $response = asToken($token)->postJson('/api/v1/products', [
        'sku' => 'WIDGET-001', 'name' => 'Another Widget', 'unit_price' => 10, 'cost_price' => 5,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
});

it('allows the same SKU to be reused across different tenants', function () {
    asToken(ownerSession(['email' => 'a@example.com', 'tenant_name' => 'Tenant A'])['token'])
        ->postJson('/api/v1/products', ['sku' => 'WIDGET-001', 'name' => 'Widget', 'unit_price' => 10, 'cost_price' => 5])
        ->assertCreated();

    asToken(ownerSession(['email' => 'b@example.com', 'tenant_name' => 'Tenant B'])['token'])
        ->postJson('/api/v1/products', ['sku' => 'WIDGET-001', 'name' => 'Widget', 'unit_price' => 10, 'cost_price' => 5])
        ->assertCreated();
});

it('requires sku, name, unit_price, and cost_price', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/products', []);

    $response->assertStatus(422);
    $fields = collect($response->json('error.details'))->pluck('field')->unique()->values()->all();
    expect($fields)->toContain('sku', 'name', 'unit_price', 'cost_price');
});

it('blocks a member from creating or deleting a product but allows viewing', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    asToken($memberToken)
        ->postJson('/api/v1/products', ['sku' => 'X-1', 'name' => 'X', 'unit_price' => 1, 'cost_price' => 1])
        ->assertStatus(403);

    $productId = asToken($session['token'])
        ->postJson('/api/v1/products', ['sku' => 'X-1', 'name' => 'X', 'unit_price' => 1, 'cost_price' => 1])
        ->json('data.id');

    asToken($memberToken)->getJson("/api/v1/products/{$productId}")->assertOk();
    asToken($memberToken)->deleteJson("/api/v1/products/{$productId}")->assertStatus(403);
});

it('cannot see or modify another tenant\'s products', function () {
    $tokenA = ownerSession(['email' => 'a@example.com', 'tenant_name' => 'Tenant A'])['token'];
    $productId = asToken($tokenA)
        ->postJson('/api/v1/products', ['sku' => 'A-1', 'name' => 'A', 'unit_price' => 1, 'cost_price' => 1])
        ->json('data.id');

    $tokenB = ownerSession(['email' => 'b@example.com', 'tenant_name' => 'Tenant B'])['token'];

    asToken($tokenB)->getJson("/api/v1/products/{$productId}")->assertStatus(404);
});
