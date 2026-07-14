<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InventoryMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'inventory_movement',
            'attributes' => [
                'movement_type' => $this->movementType,
                'quantity' => $this->quantity,
                'reason' => $this->reason,
                'created_by_user_id' => $this->createdByUserId,
                'created_at' => $this->createdAt->format(DATE_ATOM),
            ],
        ];
    }
}
