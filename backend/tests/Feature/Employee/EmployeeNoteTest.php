<?php

declare(strict_types=1);

it('lets HR add and list notes for an employee', function () {
    $session = ownerSession();
    $hrToken = tokenForRole($session['tenant_id'], 'HR', 'hr@example.com');

    $employeeId = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    $create = asToken($hrToken)->postJson("/api/v1/employees/{$employeeId}/notes", [
        'note' => 'Completed onboarding successfully.',
    ]);
    $create->assertCreated();
    $create->assertJsonPath('data.attributes.note', 'Completed onboarding successfully.');

    $list = asToken($hrToken)->getJson("/api/v1/employees/{$employeeId}/notes");
    $list->assertOk();
    expect($list->json('data'))->toHaveCount(1);
});

it('blocks a plain member with no management responsibility from adding notes', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $employeeId = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    $response = asToken($memberToken)->postJson("/api/v1/employees/{$employeeId}/notes", ['note' => 'Anything']);

    $response->assertStatus(403);
});

it('requires note content', function () {
    $session = ownerSession();
    $employeeId = asToken($session['token'])->postJson('/api/v1/employees', createEmployeePayload())->json('data.id');

    $response = asToken($session['token'])->postJson("/api/v1/employees/{$employeeId}/notes", []);

    $response->assertStatus(422);
});
