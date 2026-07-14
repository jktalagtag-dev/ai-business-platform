<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'product',
            'attributes' => [
                'sku' => $this->sku,
                'name' => $this->name,
                'description' => $this->description,
                'category_id' => $this->categoryId,
                'unit_price' => $this->unitPrice,
                'cost_price' => $this->costPrice,
                'is_active' => $this->isActive,
            ],
        ];
    }
}
