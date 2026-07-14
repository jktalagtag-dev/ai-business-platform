<?php

declare(strict_types=1);

namespace App\Http\Resources\Ticket;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TicketCommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'ticket_comment',
            'attributes' => [
                'ticket_id' => $this->ticketId,
                'author_employee_id' => $this->authorEmployeeId,
                'body' => $this->body,
                'is_internal' => $this->isInternal,
                'created_at' => $this->createdAt->format(DATE_ATOM),
            ],
        ];
    }
}
