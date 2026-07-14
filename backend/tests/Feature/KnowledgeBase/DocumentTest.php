<?php

declare(strict_types=1);

use App\Application\Contracts\Services\AI\AiProviderInterface;
use App\Application\Contracts\Services\KnowledgeBase\PdfTextExtractorInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeAiProvider;
use Tests\Support\FakePdfTextExtractor;

/**
 * QUEUE_CONNECTION=sync in tests (phpunit.xml), so
 * ProcessKnowledgeBaseDocumentJob runs inline within the upload request —
 * as long as the fakes below are bound first, the document is fully
 * processed by the time the HTTP response comes back.
 */
function bindKnowledgeBaseFakes(): array
{
    $extractor = new FakePdfTextExtractor;
    $provider = new FakeAiProvider;
    app()->instance(PdfTextExtractorInterface::class, $extractor);
    app()->instance(AiProviderInterface::class, $provider);

    return [$extractor, $provider];
}

it('uploads a PDF and processes it synchronously into ready chunks', function () {
    Storage::fake('public');
    [$extractor] = bindKnowledgeBaseFakes();
    $extractor->forNextPath(['Page one content about vacation policy.', 'Page two content about expense reports.']);

    $token = ownerSession()['token'];

    $response = asToken($token)->post('/api/v1/knowledge-base/documents', [
        'title' => 'Employee Handbook',
        'file' => UploadedFile::fake()->create('handbook.pdf', 200),
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.status', 'ready');
    $response->assertJsonPath('data.attributes.page_count', 2);
    $response->assertJsonPath('data.attributes.title', 'Employee Handbook');
});

it('defaults the title to the original filename when none is given', function () {
    Storage::fake('public');
    bindKnowledgeBaseFakes();

    $token = ownerSession()['token'];

    $response = asToken($token)->post('/api/v1/knowledge-base/documents', [
        'file' => UploadedFile::fake()->create('quarterly-report.pdf', 100),
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.title', 'quarterly-report');
});

it('marks a document failed when no extractable text is found', function () {
    Storage::fake('public');
    [$extractor] = bindKnowledgeBaseFakes();
    $extractor->forNextPath([]);

    $token = ownerSession()['token'];

    $response = asToken($token)->post('/api/v1/knowledge-base/documents', [
        'file' => UploadedFile::fake()->create('scanned.pdf', 100),
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.attributes.status', 'failed');
    expect($response->json('data.attributes.error_message'))->toContain('No extractable text');
});

it('rejects a non-PDF upload', function () {
    Storage::fake('public');
    bindKnowledgeBaseFakes();

    $token = ownerSession()['token'];

    $response = asToken($token)->post('/api/v1/knowledge-base/documents', [
        'file' => UploadedFile::fake()->create('notes.txt', 50),
    ]);

    $response->assertStatus(422);
});

it('blocks a plain member from uploading a document', function () {
    Storage::fake('public');
    bindKnowledgeBaseFakes();

    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $response = asToken($memberToken)->post('/api/v1/knowledge-base/documents', [
        'file' => UploadedFile::fake()->create('handbook.pdf', 100),
    ]);

    $response->assertStatus(403);
});

it('blocks HR (no knowledge_base.view) from listing documents', function () {
    $session = ownerSession();
    $hrToken = tokenForRole($session['tenant_id'], 'HR', 'hr@example.com');

    asToken($hrToken)->getJson('/api/v1/knowledge-base/documents')->assertStatus(403);
});

it('lets a plain member list and view documents', function () {
    Storage::fake('public');
    bindKnowledgeBaseFakes();

    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    asToken($session['token'])->post('/api/v1/knowledge-base/documents', [
        'file' => UploadedFile::fake()->create('handbook.pdf', 100),
    ])->assertCreated();

    $list = asToken($memberToken)->getJson('/api/v1/knowledge-base/documents');
    $list->assertOk();
    expect($list->json('data'))->toHaveCount(1);
});

it('deletes a document', function () {
    Storage::fake('public');
    bindKnowledgeBaseFakes();

    $token = ownerSession()['token'];
    $documentId = asToken($token)->post('/api/v1/knowledge-base/documents', [
        'file' => UploadedFile::fake()->create('handbook.pdf', 100),
    ])->json('data.id');

    asToken($token)->deleteJson("/api/v1/knowledge-base/documents/{$documentId}")->assertOk();
    asToken($token)->getJson("/api/v1/knowledge-base/documents/{$documentId}")->assertStatus(404);
});

it('blocks a plain member from deleting a document', function () {
    Storage::fake('public');
    bindKnowledgeBaseFakes();

    $session = ownerSession();
    $memberToken = tokenForRole($session['tenant_id'], 'Member', 'member@example.com');

    $documentId = asToken($session['token'])->post('/api/v1/knowledge-base/documents', [
        'file' => UploadedFile::fake()->create('handbook.pdf', 100),
    ])->json('data.id');

    asToken($memberToken)->deleteJson("/api/v1/knowledge-base/documents/{$documentId}")->assertStatus(403);
});
