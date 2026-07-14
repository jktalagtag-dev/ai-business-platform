<?php

declare(strict_types=1);

namespace App\Domain\KnowledgeBase;

final class Document
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $uploadedByUserId,
        public readonly string $title,
        public readonly string $originalFilename,
        public readonly string $filePath,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly string $status,
        public readonly ?string $errorMessage,
        public readonly ?int $pageCount,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {}

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
