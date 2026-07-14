<?php

declare(strict_types=1);

namespace App\Http\Resources\Rbac;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'],
            'user' => new UserResource($this->resource['user']),
            'membership' => new TenantMembershipResource($this->resource['membership']),
        ];
    }
}
