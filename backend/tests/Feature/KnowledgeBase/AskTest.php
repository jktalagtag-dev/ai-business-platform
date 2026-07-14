<?php

declare(strict_types=1);

use App\Application\Contracts\Services\AI\AiProviderInterface;
use App\Application\Contracts\Services\KnowledgeBase\PdfTextExtractorInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeAiProvider;
use Tests\Support\FakePdfTextExtractor;

it('answers a question with citations pointing back to the source document and page', function () {
    Storage::fake('public');
    $extractor = new FakePdfTextExtractor;
    $provider = new FakeAiProvider;
    app()->instance(PdfTextExtractorInterface::class, $extractor);
    app()->instance(AiProviderInterface::class, $provider);

    $extractor->forNextPath([
        'Employees receive twenty days of paid vacation per year, accrued monthly.',
        'Expense reports must be submitted within thirty days of the purchase.',
    ]);

    $token = ownerSession()['token'];
    asToken($token)->post('/api/v1/knowledge-base/documents', [
        'title' => 'Employee Handbook',
        'file' => UploadedFile::fake()->create('handbook.pdf', 100),
    ])->assertCreated();

    $provider->queueTextReply('You get twenty vacation days per year [1].', promptTokens: 30, completionTokens: 12);

    $response = asToken($token)->postJson('/api/v1/knowledge-base/ask', [
        'query' => 'How many vacation days do employees get?',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.answer', 'You get twenty vacation days per year [1].');
    $response->assertJsonPath('data.usage.prompt_tokens', 30);
    $response->assertJsonPath('data.usage.completion_tokens', 12);

    $citations = $response->json('data.citations');
    expect($citations)->not->toBeEmpty();
    expect($citations[0]['title'])->toBe('Employee Handbook');
    expect($citations[0])->toHaveKeys(['number', 'document_id', 'chunk_index', 'page_number', 'snippet', 'score']);
});

it('ranks the more relevant chunk first', function () {
    Storage::fake('public');
    $extractor = new FakePdfTextExtractor;
    $provider = new FakeAiProvider;
    app()->instance(PdfTextExtractorInterface::class, $extractor);
    app()->instance(AiProviderInterface::class, $provider);

    $extractor->forNextPath([
        'Employees receive twenty days of paid vacation per year, accrued monthly.',
        'Expense reports must be submitted within thirty days of the purchase.',
    ]);

    $token = ownerSession()['token'];
    asToken($token)->post('/api/v1/knowledge-base/documents', [
        'file' => UploadedFile::fake()->create('handbook.pdf', 100),
    ])->assertCreated();

    $provider->queueTextReply('Answer.');

    $response = asToken($token)->postJson('/api/v1/knowledge-base/ask', ['query' => 'vacation days per year']);

    $response->assertOk();
    expect($response->json('data.citations.0.page_number'))->toBe(1);
});

it('returns a fallback answer with no citations when the knowledge base is empty', function () {
    $provider = new FakeAiProvider;
    app()->instance(AiProviderInterface::class, $provider);

    $token = ownerSession()['token'];

    $response = asToken($token)->postJson('/api/v1/knowledge-base/ask', ['query' => 'anything']);

    $response->assertOk();
    $response->assertJsonPath('data.citations', []);
    expect($provider->calls)->toBeEmpty();
});

it('blocks HR (no knowledge_base.view) from asking', function () {
    $session = ownerSession();
    $hrToken = tokenForRole($session['tenant_id'], 'HR', 'hr@example.com');

    asToken($hrToken)->postJson('/api/v1/knowledge-base/ask', ['query' => 'anything'])->assertStatus(403);
});

it('lets a plain member ask questions', function () {
    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');
    $provider = new FakeAiProvider;
    app()->instance(AiProviderInterface::class, $provider);

    asToken($memberToken)->postJson('/api/v1/knowledge-base/ask', ['query' => 'anything'])->assertOk();
});

it('requires a non-empty query', function () {
    asToken(ownerSession()['token'])->postJson('/api/v1/knowledge-base/ask', [])->assertStatus(422);
});
