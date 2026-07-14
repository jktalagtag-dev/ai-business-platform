<?php

declare(strict_types=1);

it('creates, lists, updates, and deletes a position', function () {
    $token = ownerSession()['token'];

    $create = asToken($token)->postJson('/api/v1/positions', ['title' => 'Software Engineer']);
    $create->assertCreated();
    $positionId = $create->json('data.id');

    $index = asToken($token)->getJson('/api/v1/positions');
    expect($index->json('data'))->toHaveCount(1);

    $update = asToken($token)->patchJson("/api/v1/positions/{$positionId}", ['title' => 'Senior Software Engineer']);
    $update->assertOk();
    $update->assertJsonPath('data.attributes.title', 'Senior Software Engineer');

    $destroy = asToken($token)->deleteJson("/api/v1/positions/{$positionId}");
    $destroy->assertOk();

    asToken($token)->getJson("/api/v1/positions/{$positionId}")->assertStatus(404);
});

it('rejects a duplicate position title within the same tenant', function () {
    $token = ownerSession()['token'];

    asToken($token)->postJson('/api/v1/positions', ['title' => 'Software Engineer'])->assertCreated();

    $response = asToken($token)->postJson('/api/v1/positions', ['title' => 'Software Engineer']);

    $response->assertStatus(422);
});

it('blocks a member from managing positions', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    asToken($memberToken)->postJson('/api/v1/positions', ['title' => 'Anything'])->assertStatus(403);
});
