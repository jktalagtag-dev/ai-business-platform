<?php

declare(strict_types=1);

namespace App\Application\DTOs\Ai;

final class CreateConversationData
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $systemPrompt,
        public readonly ?string $model,
    ) {}
}
