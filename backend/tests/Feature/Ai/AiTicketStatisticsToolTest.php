<?php

declare(strict_types=1);

use App\Application\Contracts\Services\AI\AiProviderInterface;
use Tests\Support\FakeAiProvider;

it('lets the get_ticket_statistics tool return real, scope-respecting data via the existing authorized service', function () {
    $fake = new FakeAiProvider;
    app()->instance(AiProviderInterface::class, $fake);

    $session = ownerSession();
    $employee = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $employee['user_id']]);

    asToken($employee['token'])->postJson('/api/v1/tickets', [
        'type' => 'hardware',
        'priority' => 'critical',
        'subject' => 'Server down',
        'description' => 'The main server is unresponsive.',
    ])->assertCreated();

    $fake->queueToolCall('call_1', 'get_ticket_statistics', []);
    $fake->queueTextReply('You have 1 open critical ticket.');

    $conversationId = asToken($session['token'])->postJson('/api/v1/ai/conversations', [])->json('data.id');

    $response = asToken($session['token'])->post("/api/v1/ai/conversations/{$conversationId}/messages", [
        'content' => 'How many open tickets are there?',
    ]);

    $streamed = $response->streamedContent();
    $toolResultLine = collect(explode("\n\n", $streamed))->first(fn ($frame) => str_contains($frame, 'event: tool_result'));
    $data = json_decode(trim(str_replace('data:', '', explode("\n", $toolResultLine)[1])), true);

    expect($data['result']['open_count'])->toBe(1);
    expect($data['result']['by_priority']['critical'])->toBe(1);
});

it('feeds back an authorization error from get_ticket_statistics when the caller has no ticket access at all', function () {
    $fake = new FakeAiProvider;
    app()->instance(AiProviderInterface::class, $fake);

    $session = ownerSession();
    // A Member with no linked employee record fails TicketPolicy::viewAny().
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $fake->queueToolCall('call_1', 'get_ticket_statistics', []);
    $fake->queueTextReply('I could not fetch that.');

    $conversationId = asToken($memberToken)->postJson('/api/v1/ai/conversations', [])->json('data.id');

    $response = asToken($memberToken)->post("/api/v1/ai/conversations/{$conversationId}/messages", [
        'content' => 'How many open tickets are there?',
    ]);

    $streamed = $response->streamedContent();
    $toolResultLine = collect(explode("\n\n", $streamed))->first(fn ($frame) => str_contains($frame, 'event: tool_result'));
    $data = json_decode(trim(str_replace('data:', '', explode("\n", $toolResultLine)[1])), true);

    expect($data['result'])->toHaveKey('error');
});
