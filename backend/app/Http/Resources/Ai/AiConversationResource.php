<?php

declare(strict_types=1);

namespace App\Http\Resources\Ai;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AiConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'ai_conversation',
            'attributes' => [
                'title' => $this->title,
                'system_prompt' => $this->systemPrompt,
                'provider' => $this->provider,
                'model' => $this->model,
                'total_prompt_tokens' => $this->totalPromptTokens,
                'total_completion_tokens' => $this->totalCompletionTokens,
                'created_at' => $this->createdAt->format(DATE_ATOM),
                'updated_at' => $this->updatedAt->format(DATE_ATOM),
            ],
        ];
    }
}
