<?php

declare(strict_types=1);

namespace App\Domain\Ai;

final class AiConversation
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $userId,
        public readonly ?string $title,
        public readonly ?string $systemPrompt,
        public readonly string $provider,
        public readonly string $model,
        public readonly int $totalPromptTokens,
        public readonly int $totalCompletionTokens,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {}

    public function isOwnedBy(string $userId): bool
    {
        return $this->userId === $userId;
    }

    public function systemPromptOrDefault(string $default): string
    {
        return $this->systemPrompt ?? $default;
    }
}
