<?php

declare(strict_types=1);

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmployeeNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'employee_note',
            'attributes' => [
                'employee_id' => $this->employeeId,
                'author_user_id' => $this->authorUserId,
                'note' => $this->note,
                'created_at' => $this->createdAt->format(DATE_ATOM),
            ],
        ];
    }
}
