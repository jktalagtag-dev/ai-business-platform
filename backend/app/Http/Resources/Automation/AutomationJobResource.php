<?php

declare(strict_types=1);

namespace App\Http\Resources\Automation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AutomationJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'automation_job',
            'attributes' => [
                'workflow_id' => $this->workflowId,
                'trigger' => $this->trigger,
                'status' => $this->status,
                'attempts' => $this->attempts,
                'max_attempts' => $this->maxAttempts,
                'context' => $this->context,
                'error' => $this->error,
                'scheduled_at' => optional($this->scheduledAt)->format(DATE_ATOM),
                'started_at' => optional($this->startedAt)->format(DATE_ATOM),
                'finished_at' => optional($this->finishedAt)->format(DATE_ATOM),
                'created_at' => $this->createdAt->format(DATE_ATOM),
                'updated_at' => $this->updatedAt->format(DATE_ATOM),
            ],
        ];
    }
}
