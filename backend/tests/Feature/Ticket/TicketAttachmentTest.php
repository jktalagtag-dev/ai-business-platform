<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('uploads an attachment to a ticket', function () {
    Storage::fake('public');
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($requester['token'])->post("/api/v1/tickets/{$ticketId}/attachments", [
        'file' => UploadedFile::fake()->create('screenshot.png', 500),
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.original_filename', 'screenshot.png');
});

it('rejects an attachment upload without a file', function () {
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($requester['token'])->post("/api/v1/tickets/{$ticketId}/attachments", []);

    $response->assertStatus(422);
});

it('rejects an attachment larger than the configured limit', function () {
    Storage::fake('public');
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    $response = asToken($requester['token'])->post("/api/v1/tickets/{$ticketId}/attachments", [
        'file' => UploadedFile::fake()->create('large.zip', 20480),
    ]);

    $response->assertStatus(422);
});

it('lists attachments for a ticket', function () {
    Storage::fake('public');
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');
    asToken($requester['token'])->post("/api/v1/tickets/{$ticketId}/attachments", [
        'file' => UploadedFile::fake()->create('screenshot.png', 500),
    ])->assertCreated();

    $response = asToken($requester['token'])->getJson("/api/v1/tickets/{$ticketId}/attachments");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});
