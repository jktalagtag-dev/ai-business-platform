<?php

declare(strict_types=1);

it('stores and returns the emergency contact and address', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/employees', createEmployeePayload([
        'address' => ['line1' => '123 Main St', 'city' => 'Springfield'],
        'emergency_contact' => [
            'name' => 'Mary Hopper',
            'relationship' => 'Mother',
            'phone' => '+1-555-0100',
        ],
    ]));

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.address.city', 'Springfield');
    $response->assertJsonPath('data.attributes.emergency_contact.name', 'Mary Hopper');
    $response->assertJsonPath('data.attributes.emergency_contact.relationship', 'Mother');
});

it('requires relationship and phone when an emergency contact name is given', function () {
    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/employees', createEmployeePayload([
        'emergency_contact' => ['name' => 'Mary Hopper'],
    ]));

    $response->assertStatus(422);
    $fields = collect($response->json('error.details'))->pluck('field')->unique()->values()->all();
    expect($fields)->toContain('emergency_contact.relationship', 'emergency_contact.phone');
});

it('returns 404 from /employees/me when the account has no linked employee record', function () {
    $token = ownerSession()['token'];

    asToken($token)->getJson('/api/v1/employees/me')->assertStatus(404);
});
