<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Application\Rules\ExistsInCurrentTenant;
use App\Application\Rules\UniqueInCurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateEmployeeRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'nullable', 'email', 'max:255',
                new UniqueInCurrentTenant('employees', 'email', $this->route('employee')),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'department_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('departments')],
            'position_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('positions')],
            'manager_employee_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('employees')],
            'employment_type' => ['sometimes', Rule::in(['full_time', 'part_time', 'contractor', 'intern'])],
            'employment_status' => ['sometimes', Rule::in(['active', 'on_leave', 'suspended', 'terminated'])],
            'hire_date' => ['required', 'date'],
            'termination_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:hire_date'],
            'address' => ['sometimes', 'nullable', 'array'],
            'emergency_contact' => ['sometimes', 'nullable', 'array'],
            'emergency_contact.name' => ['required_with:emergency_contact', 'string', 'max:255'],
            'emergency_contact.relationship' => ['required_with:emergency_contact', 'string', 'max:100'],
            'emergency_contact.phone' => ['required_with:emergency_contact', 'string', 'max:50'],
            'emergency_contact.email' => ['sometimes', 'nullable', 'email'],
            'bio' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('manager_employee_id') === $this->route('employee')) {
                $validator->errors()->add('manager_employee_id', 'An employee cannot be their own manager.');
            }
        });
    }
}
