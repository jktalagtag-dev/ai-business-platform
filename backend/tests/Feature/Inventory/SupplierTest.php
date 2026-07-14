<?php

declare(strict_types=1);

it('creates, lists, updates, and deletes a supplier', function () {
    $token = ownerSession()['token'];

    $create = asToken($token)->postJson('/api/v1/suppliers', [
        'name' => 'Acme Supplies Ltd.',
        'contact_email' => 'sales@acme.test',
        'status' => 'active',
    ]);
    $create->assertCreated();
    $supplierId = $create->json('data.id');

    $index = asToken($token)->getJson('/api/v1/suppliers');
    expect($index->json('data'))->toHaveCount(1);

    $update = asToken($token)->patchJson("/api/v1/suppliers/{$supplierId}", [
        'name' => 'Acme Supplies Ltd.', 'status' => 'inactive',
    ]);
    $update->assertOk();
    $update->assertJsonPath('data.attributes.status', 'inactive');

    $destroy = asToken($token)->deleteJson("/api/v1/suppliers/{$supplierId}");
    $destroy->assertOk();

    asToken($token)->getJson("/api/v1/suppliers/{$supplierId}")->assertStatus(404);
});

it('filters suppliers by status and search', function () {
    $token = ownerSession()['token'];

    asToken($token)->postJson('/api/v1/suppliers', ['name' => 'Acme', 'status' => 'active'])->assertCreated();
    asToken($token)->postJson('/api/v1/suppliers', ['name' => 'Globex', 'status' => 'inactive'])->assertCreated();

    $byStatus = asToken($token)->getJson('/api/v1/suppliers?status=inactive');
    expect($byStatus->json('data'))->toHaveCount(1);
    $byStatus->assertJsonPath('data.0.attributes.name', 'Globex');

    $bySearch = asToken($token)->getJson('/api/v1/suppliers?search=Acme');
    expect($bySearch->json('data'))->toHaveCount(1);
});

it('requires a name and rejects an invalid status', function () {
    $token = ownerSession()['token'];

    asToken($token)->postJson('/api/v1/suppliers', [])
        ->assertStatus(422);

    asToken($token)->postJson('/api/v1/suppliers', ['name' => 'Acme', 'status' => 'not-a-status'])
        ->assertStatus(422);
});

it('rejects an invalid contact_email format', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/suppliers', [
        'name' => 'Acme', 'contact_email' => 'not-an-email',
    ]);

    $response->assertStatus(422);
});

it('blocks a member from managing suppliers but allows viewing', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    asToken($memberToken)->postJson('/api/v1/suppliers', ['name' => 'Acme'])->assertStatus(403);
    asToken($memberToken)->getJson('/api/v1/suppliers')->assertOk();
});

it('records audit log entries for supplier create, update, and delete', function () {
    $token = ownerSession()['token'];

    $supplierId = asToken($token)->postJson('/api/v1/suppliers', ['name' => 'Acme'])->json('data.id');
    asToken($token)->patchJson("/api/v1/suppliers/{$supplierId}", ['name' => 'Acme Corp'])->assertOk();
    asToken($token)->deleteJson("/api/v1/suppliers/{$supplierId}")->assertOk();

    $logs = asToken($token)->getJson("/api/v1/audit-logs?subject_type=supplier&subject_id={$supplierId}");

    $logs->assertOk();
    $actions = collect($logs->json('data'))->pluck('attributes.action')->all();
    expect($actions)->toBe(['supplier.deleted', 'supplier.updated', 'supplier.created']);
});
