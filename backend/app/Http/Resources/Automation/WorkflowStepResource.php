<?php

declare(strict_types=1);

namespace App\Http\Resources\Automation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WorkflowStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'workflow_step',
            'attributes' => [
                'step_order' => $this->stepOrder,
                'step_type' => $this->type,
                'config' => $this->config,
            ],
        ];
    }
}
