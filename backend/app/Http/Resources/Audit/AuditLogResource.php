<?php

declare(strict_types=1);

namespace App\Http\Resources\Audit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'audit_log',
            'attributes' => [
                'actor_user_id' => $this->actor_user_id,
                'action' => $this->action,
                'subject_type' => $this->subject_type,
                'subject_id' => $this->subject_id,
                'changes' => $this->changes,
                'ip_address' => $this->ip_address,
                'created_at' => optional($this->created_at)->toIso8601String(),
            ],
        ];
    }
}
