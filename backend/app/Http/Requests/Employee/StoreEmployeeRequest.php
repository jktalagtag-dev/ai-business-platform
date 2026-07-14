<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Application\Contracts\Services\TenantContextInterface;
use App\Application\Rules\ExistsInCurrentTenant;
use App\Application\Rules\UniqueInCurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreEmployeeRequest extends FormRequest
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
            'user_id' => ['sometimes', 'nullable', 'string', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', new UniqueInCurrentTenant('employees', 'email')],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'department_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('departments')],
            'position_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('positions')],
            'manager_employee_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('employees')],
            'employment_type' => ['sometimes', Rule::in(['full_time', 'part_time', 'contractor', 'intern'])],
            'employment_status' => ['sometimes', Rule::in(['active', 'on_leave', 'suspended', 'terminated'])],
            'hire_date' => ['required', 'date'],
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
            $userId = $this->input('user_id');

            if ($userId === null) {
                return;
            }

            $isMember = DB::table('tenant_users')
                ->where('tenant_id', app(TenantContextInterface::class)->tenantId())
                ->where('user_id', $userId)
                ->exists();

            if (! $isMember) {
                $validator->errors()->add('user_id', 'The selected user is not a member of this tenant.');
            }
        });
    }
}
