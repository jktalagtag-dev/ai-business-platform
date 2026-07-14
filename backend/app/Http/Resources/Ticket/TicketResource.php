<?php

declare(strict_types=1);

namespace App\Http\Resources\Ticket;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'ticket',
            'attributes' => [
                'ticket_number' => $this->ticketNumber,
                'employee_id' => $this->employeeId,
                'assigned_technician_id' => $this->assignedTechnicianId,
                'department_id' => $this->departmentId,
                'ticket_type' => $this->type,
                'priority' => $this->priority,
                'status' => $this->status,
                'subject' => $this->subject,
                'description' => $this->description,
                'resolution_notes' => $this->resolutionNotes,
                'resolved_at' => optional($this->resolvedAt)->format(DATE_ATOM),
                'closed_at' => optional($this->closedAt)->format(DATE_ATOM),
                'sla_breached_at' => optional($this->slaBreachedAt)->format(DATE_ATOM),
                'created_at' => $this->createdAt->format(DATE_ATOM),
            ],
        ];
    }
}
