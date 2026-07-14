<?php

declare(strict_types=1);

namespace App\Http\Resources\Ticket;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class TicketAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'ticket_attachment',
            'attributes' => [
                'ticket_id' => $this->ticketId,
                'uploaded_by_employee_id' => $this->uploadedByEmployeeId,
                'original_filename' => $this->originalFilename,
                'mime_type' => $this->mimeType,
                'size_bytes' => $this->sizeBytes,
                // Mirrors Employee's avatar upload precedent (public disk,
                // predictable URL). Ticket attachments can be more sensitive
                // than an avatar — production should likely move this to a
                // private disk with signed, expiring URLs; flagged here
                // rather than silently deferred.
                'url' => Storage::disk('public')->url($this->filePath),
                'created_at' => $this->createdAt->format(DATE_ATOM),
            ],
        ];
    }
}
