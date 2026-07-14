<?php

declare(strict_types=1);

namespace App\Http\Resources\Rbac;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantMembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'tenant_membership',
            'attributes' => [
                'tenant' => [
                    'id' => $this->tenantId,
                    'name' => $this->tenantName,
                    'slug' => $this->tenantSlug,
                ],
                'role' => [
                    'id' => $this->role->id,
                    'name' => $this->role->name,
                    'permissions' => $this->role->permissionKeys,
                ],
                'status' => $this->status,
            ],
        ];
    }
}
