<?php

declare(strict_types=1);

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DepartmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'department',
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'parent_department_id' => $this->parentDepartmentId,
                'manager_employee_id' => $this->managerEmployeeId,
            ],
        ];
    }
}
