<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'supplier',
            'attributes' => [
                'name' => $this->name,
                'contact_email' => $this->contactEmail,
                'contact_phone' => $this->contactPhone,
                'address' => $this->address,
                'status' => $this->status,
            ],
        ];
    }
}
