<?php

declare(strict_types=1);

namespace App\Http\Resources\Rbac;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'role',
            'attributes' => [
                'name' => $this->name,
                'is_system' => $this->isSystem,
                'permissions' => $this->permissionKeys,
            ],
        ];
    }
}
