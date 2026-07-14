<?php

declare(strict_types=1);

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PositionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'position',
            'attributes' => [
                'title' => $this->title,
                'description' => $this->description,
            ],
        ];
    }
}
