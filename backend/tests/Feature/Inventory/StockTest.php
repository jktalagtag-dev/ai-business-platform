<?php

declare(strict_types=1);

function createProduct(string $token, string $sku = 'WIDGET-001'): string
{
    return asToken($token)
        ->postJson('/api/v1/products', ['sku' => $sku, 'name' => 'Widget', 'unit_price' => 10, 'cost_price' => 5])
        ->json('data.id');
}

it('increases stock via an inbound adjustment and records a movement', function () {
    $token = ownerSession()['token'];
    $productId = createProduct($token);

    $adjust = asToken($token)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => 100,
        'movement_type' => 'inbound',
        'reason' => 'Initial stock intake',
    ]);

    $adjust->assertOk();
    $adjust->assertJsonPath('data.attributes.quantity_on_hand', 100);

    $movements = asToken($token)->getJson("/api/v1/stock/{$productId}/movements");
    $movements->assertOk();
    expect($movements->json('data'))->toHaveCount(1);
    $movements->assertJsonPath('data.0.attributes.quantity', 100);
    $movements->assertJsonPath('data.0.attributes.movement_type', 'inbound');
});

it('decreases stock via an outbound adjustment', function () {
    $token = ownerSession()['token'];
    $productId = createProduct($token);

    asToken($token)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => 50, 'movement_type' => 'inbound',
    ])->assertOk();

    $outbound = asToken($token)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => -20, 'movement_type' => 'outbound', 'reason' => 'Sold',
    ]);

    $outbound->assertOk();
    $outbound->assertJsonPath('data.attributes.quantity_on_hand', 30);
});

it('rejects an outbound adjustment that would take stock negative', function () {
    $token = ownerSession()['token'];
    $productId = createProduct($token);

    asToken($token)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => 10, 'movement_type' => 'inbound',
    ])->assertOk();

    $response = asToken($token)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => -50, 'movement_type' => 'outbound',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');

    $stock = asToken($token)->getJson("/api/v1/stock/{$productId}");
    $stock->assertJsonPath('data.attributes.quantity_on_hand', 10);
});

it('rejects inconsistent sign/movement_type combinations', function () {
    $token = ownerSession()['token'];
    $productId = createProduct($token);

    asToken($token)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => -10, 'movement_type' => 'inbound',
    ])->assertStatus(422);

    asToken($token)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => 10, 'movement_type' => 'outbound',
    ])->assertStatus(422);

    asToken($token)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => 0, 'movement_type' => 'adjustment',
    ])->assertStatus(422);
});

it('lists only low-stock items when filtered', function () {
    $token = ownerSession()['token'];
    $lowId = createProduct($token, 'LOW-1');
    $healthyId = createProduct($token, 'HEALTHY-1');

    // Default reorder_point is 0, so any item with quantity_on_hand still at
    // 0 is already "low stock" — push the healthy one comfortably above it.
    asToken($token)->postJson("/api/v1/stock/{$healthyId}/adjust", [
        'quantity' => 100, 'movement_type' => 'inbound',
    ])->assertOk();

    $response = asToken($token)->getJson('/api/v1/stock?low_stock=true');

    $response->assertOk();
    $skus = collect($response->json('data'))->pluck('attributes.product_sku')->all();
    expect($skus)->toContain('LOW-1')->not->toContain('HEALTHY-1');
});

it('blocks a member from adjusting stock but allows viewing', function () {
    $session = ownerSession();
    $productId = createProduct($session['token']);
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    asToken($memberToken)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => 10, 'movement_type' => 'inbound',
    ])->assertStatus(403);

    asToken($memberToken)->getJson("/api/v1/stock/{$productId}")->assertOk();
});

it('records an audit log entry for a stock adjustment', function () {
    $token = ownerSession()['token'];
    $productId = createProduct($token);

    asToken($token)->postJson("/api/v1/stock/{$productId}/adjust", [
        'quantity' => 25, 'movement_type' => 'inbound', 'reason' => 'Restock',
    ])->assertOk();

    $logs = asToken($token)->getJson('/api/v1/audit-logs?subject_type=inventory_item');

    $logs->assertOk();
    expect($logs->json('data'))->toHaveCount(1);
    $logs->assertJsonPath('data.0.attributes.action', 'inventory.adjusted');
    expect($logs->json('data.0.attributes.changes.quantity_delta'))->toBe(25);
});
