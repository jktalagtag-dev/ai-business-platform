<?php

declare(strict_types=1);

use App\Application\Contracts\Services\AI\AiProviderInterface;
use App\Application\Contracts\Services\KnowledgeBase\PdfTextExtractorInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeAiProvider;
use Tests\Support\FakePdfTextExtractor;

it('lets the AI Assistant chat cite the knowledge base via the search_knowledge_base tool', function () {
    Storage::fake('public');
    $extractor = new FakePdfTextExtractor;
    $fake = new FakeAiProvider;
    app()->instance(PdfTextExtractorInterface::class, $extractor);
    app()->instance(AiProviderInterface::class, $fake);

    $extractor->forNextPath(['Our office closes at 5pm on Fridays.']);

    $token = ownerSession()['token'];
    asToken($token)->post('/api/v1/knowledge-base/documents', [
        'title' => 'Office Policy',
        'file' => UploadedFile::fake()->create('policy.pdf', 100),
    ])->assertCreated();

    $fake->queueToolCall('call_1', 'search_knowledge_base', ['query' => 'Friday closing time']);
    $fake->queueTextReply('The office closes at 5pm on Fridays [1].');

    $conversationId = asToken($token)->postJson('/api/v1/ai/conversations', [])->json('data.id');

    $response = asToken($token)->post("/api/v1/ai/conversations/{$conversationId}/messages", [
        'content' => 'When does the office close on Fridays?',
    ]);

    $streamed = $response->streamedContent();
    $toolResultLine = collect(explode("\n\n", $streamed))->first(fn ($frame) => str_contains($frame, 'event: tool_result'));
    $data = json_decode(trim(str_replace('data:', '', explode("\n", $toolResultLine)[1])), true);

    expect($data['name'])->toBe('search_knowledge_base');
    expect($data['result']['results'])->not->toBeEmpty();
    expect($data['result']['results'][0]['title'])->toBe('Office Policy');
});
