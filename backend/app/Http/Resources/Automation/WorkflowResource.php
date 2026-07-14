<?php

declare(strict_types=1);

namespace App\Http\Resources\Automation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'workflow',
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->status,
                'created_by_user_id' => $this->createdByUserId,
                'last_triggered_at' => optional($this->lastTriggeredAt)->format(DATE_ATOM),
                'created_at' => $this->createdAt->format(DATE_ATOM),
                'updated_at' => $this->updatedAt->format(DATE_ATOM),
            ],
        ];
    }
}
