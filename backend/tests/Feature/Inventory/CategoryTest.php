<?php

declare(strict_types=1);

it('creates, lists, updates, and deletes a category as owner', function () {
    $session = ownerSession();
    $token = $session['token'];

    $create = asToken($token)->postJson('/api/v1/categories', ['name' => 'Electronics']);
    $create->assertCreated();
    $create->assertJsonPath('data.attributes.name', 'Electronics');
    $categoryId = $create->json('data.id');

    $index = asToken($token)->getJson('/api/v1/categories');
    $index->assertOk();
    expect($index->json('data'))->toHaveCount(1);
    expect($index->json('meta.pagination'))->toHaveKeys(['next_cursor', 'prev_cursor', 'per_page']);

    $show = asToken($token)->getJson("/api/v1/categories/{$categoryId}");
    $show->assertOk();
    $show->assertJsonPath('data.attributes.name', 'Electronics');

    $update = asToken($token)->patchJson("/api/v1/categories/{$categoryId}", ['name' => 'Consumer Electronics']);
    $update->assertOk();
    $update->assertJsonPath('data.attributes.name', 'Consumer Electronics');

    $destroy = asToken($token)->deleteJson("/api/v1/categories/{$categoryId}");
    $destroy->assertOk();

    $afterDelete = asToken($token)->getJson("/api/v1/categories/{$categoryId}");
    $afterDelete->assertStatus(404);
});

it('supports nested categories via parent_category_id', function () {
    $token = ownerSession()['token'];

    $parent = asToken($token)->postJson('/api/v1/categories', ['name' => 'Electronics']);
    $parentId = $parent->json('data.id');

    $child = asToken($token)->postJson('/api/v1/categories', [
        'name' => 'Laptops',
        'parent_category_id' => $parentId,
    ]);

    $child->assertCreated();
    $child->assertJsonPath('data.attributes.parent_category_id', $parentId);
});

it('rejects a category name without content', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/categories', ['name' => '']);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
});

it('rejects a duplicate category name under the same parent', function () {
    $token = ownerSession()['token'];

    asToken($token)->postJson('/api/v1/categories', ['name' => 'Electronics'])->assertCreated();

    $response = asToken($token)->postJson('/api/v1/categories', ['name' => 'Electronics']);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
});

it('rejects a category referencing a parent from another tenant', function () {
    $otherTenant = ownerSession(['email' => 'other@example.com', 'tenant_name' => 'Other Co']);
    $otherCategory = asToken($otherTenant['token'])
        ->postJson('/api/v1/categories', ['name' => 'Foreign Category'])
        ->json('data.id');

    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/categories', [
        'name' => 'Local Category',
        'parent_category_id' => $otherCategory,
    ]);

    $response->assertStatus(422);
});

it('blocks a member from creating a category but allows viewing', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $blocked = asToken($memberToken)->postJson('/api/v1/categories', ['name' => 'Electronics']);
    $blocked->assertStatus(403);
    $blocked->assertJsonPath('error.code', 'forbidden');

    $allowed = asToken($memberToken)->getJson('/api/v1/categories');
    $allowed->assertOk();
});

it('records an audit log entry when a category is created', function () {
    $session = ownerSession();
    $token = $session['token'];

    $category = asToken($token)->postJson('/api/v1/categories', ['name' => 'Electronics']);
    $categoryId = $category->json('data.id');

    $logs = asToken($token)->getJson('/api/v1/audit-logs?subject_type=product_category');

    $logs->assertOk();
    expect($logs->json('data'))->toHaveCount(1);
    $logs->assertJsonPath('data.0.attributes.action', 'category.created');
    $logs->assertJsonPath('data.0.attributes.subject_id', $categoryId);
});
