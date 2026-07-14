<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\Rules\ExistsInCurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'parent_department_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('departments')],
            'manager_employee_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('employees')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $id = $this->route('department');
            $parentId = $this->input('parent_department_id');
            $name = $this->input('name');

            if ($parentId === $id) {
                $validator->errors()->add('parent_department_id', 'A department cannot be its own parent.');

                return;
            }

            if (
                $name
                && app(DepartmentRepositoryInterface::class)->nameExistsUnderParent($parentId, $name, $id)
            ) {
                $validator->errors()->add('name', 'A department with this name already exists under the selected parent.');
            }
        });
    }
}
