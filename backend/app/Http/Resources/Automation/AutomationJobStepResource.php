<?php

declare(strict_types=1);

namespace App\Http\Resources\Automation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AutomationJobStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'automation_job_step',
            'attributes' => [
                'workflow_step_id' => $this->workflowStepId,
                'step_order' => $this->stepOrder,
                'step_type' => $this->type,
                'status' => $this->status,
                'output' => $this->output,
                'error' => $this->error,
                'started_at' => optional($this->startedAt)->format(DATE_ATOM),
                'finished_at' => optional($this->finishedAt)->format(DATE_ATOM),
                'created_at' => $this->createdAt->format(DATE_ATOM),
            ],
        ];
    }
}
