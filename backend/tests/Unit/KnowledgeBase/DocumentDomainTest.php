<?php

declare(strict_types=1);

use App\Domain\KnowledgeBase\Document;

function makeKbDocument(array $overrides = []): Document
{
    return new Document(
        id: $overrides['id'] ?? 'doc_01',
        tenantId: $overrides['tenantId'] ?? 'tenant_01',
        uploadedByUserId: $overrides['uploadedByUserId'] ?? 'user_01',
        title: $overrides['title'] ?? 'Handbook',
        originalFilename: $overrides['originalFilename'] ?? 'handbook.pdf',
        filePath: $overrides['filePath'] ?? 'knowledge-base/documents/handbook.pdf',
        mimeType: $overrides['mimeType'] ?? 'application/pdf',
        sizeBytes: $overrides['sizeBytes'] ?? 1024,
        status: $overrides['status'] ?? 'processing',
        errorMessage: $overrides['errorMessage'] ?? null,
        pageCount: $overrides['pageCount'] ?? null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('is ready only when status is ready', function () {
    expect(makeKbDocument(['status' => 'ready'])->isReady())->toBeTrue();
    expect(makeKbDocument(['status' => 'processing'])->isReady())->toBeFalse();
    expect(makeKbDocument(['status' => 'failed'])->isReady())->toBeFalse();
});
