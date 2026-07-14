<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use App\Application\Rules\ExistsInCurrentTenant;
use Illuminate\Foundation\Http\FormRequest;

final class AssignTicketRequest extends FormRequest
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
            'technician_employee_id' => ['required', 'string', new ExistsInCurrentTenant('employees')],
        ];
    }
}
