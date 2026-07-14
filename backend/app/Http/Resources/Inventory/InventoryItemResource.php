<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InventoryItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'inventory_item',
            'attributes' => [
                'product_id' => $this->productId,
                'product_sku' => $this->productSku,
                'product_name' => $this->productName,
                'quantity_on_hand' => $this->quantityOnHand,
                'quantity_reserved' => $this->quantityReserved,
                'reorder_point' => $this->reorderPoint,
                'reorder_quantity' => $this->reorderQuantity,
                'is_low_stock' => $this->isLowStock(),
            ],
        ];
    }
}
