<?php

declare(strict_types=1);

it('creates, lists, updates, and deletes a department as owner', function () {
    $token = ownerSession()['token'];

    $create = asToken($token)->postJson('/api/v1/departments', ['name' => 'Engineering']);
    $create->assertCreated();
    $departmentId = $create->json('data.id');

    $index = asToken($token)->getJson('/api/v1/departments');
    expect($index->json('data'))->toHaveCount(1);

    $update = asToken($token)->patchJson("/api/v1/departments/{$departmentId}", ['name' => 'Product Engineering']);
    $update->assertOk();
    $update->assertJsonPath('data.attributes.name', 'Product Engineering');

    $destroy = asToken($token)->deleteJson("/api/v1/departments/{$departmentId}");
    $destroy->assertOk();

    asToken($token)->getJson("/api/v1/departments/{$departmentId}")->assertStatus(404);
});

it('rejects a duplicate department name under the same parent', function () {
    $token = ownerSession()['token'];

    asToken($token)->postJson('/api/v1/departments', ['name' => 'Engineering'])->assertCreated();

    $response = asToken($token)->postJson('/api/v1/departments', ['name' => 'Engineering']);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'validation_failed');
});

it('rejects a department being set as its own parent', function () {
    $token = ownerSession()['token'];

    $departmentId = asToken($token)->postJson('/api/v1/departments', ['name' => 'Engineering'])->json('data.id');

    $response = asToken($token)->patchJson("/api/v1/departments/{$departmentId}", [
        'name' => 'Engineering',
        'parent_department_id' => $departmentId,
    ]);

    $response->assertStatus(422);
});

it('allows HR to manage departments but blocks a plain member', function () {
    $session = ownerSession();
    $hrToken = tokenForRole($session['tenant_id'], 'HR', 'hr@example.com');
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    asToken($hrToken)->postJson('/api/v1/departments', ['name' => 'Engineering'])->assertCreated();
    asToken($memberToken)->postJson('/api/v1/departments', ['name' => 'Sales'])->assertStatus(403);
});
