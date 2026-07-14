<?php

declare(strict_types=1);

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'employee',
            'attributes' => [
                'employee_number' => $this->employeeNumber,
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'full_name' => $this->fullName(),
                'email' => $this->email,
                'phone' => $this->phone,
                'department_id' => $this->departmentId,
                'position_id' => $this->positionId,
                'manager_employee_id' => $this->managerEmployeeId,
                'employment_type' => $this->employmentType,
                'employment_status' => $this->employmentStatus,
                'hire_date' => $this->hireDate,
                'termination_date' => $this->terminationDate,
                'address' => $this->address,
                'emergency_contact' => $this->emergencyContact?->toArray(),
                'avatar_url' => $this->avatarPath ? Storage::disk('public')->url($this->avatarPath) : null,
                'bio' => $this->bio,
            ],
        ];
    }
}
