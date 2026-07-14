<?php

declare(strict_types=1);

namespace App\Http\Resources\KnowledgeBase;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'kb_document',
            'attributes' => [
                'title' => $this->title,
                'original_filename' => $this->originalFilename,
                'mime_type' => $this->mimeType,
                'size_bytes' => $this->sizeBytes,
                'status' => $this->status,
                'error_message' => $this->errorMessage,
                'page_count' => $this->pageCount,
                'created_at' => $this->createdAt->format(DATE_ATOM),
                'updated_at' => $this->updatedAt->format(DATE_ATOM),
            ],
        ];
    }
}
