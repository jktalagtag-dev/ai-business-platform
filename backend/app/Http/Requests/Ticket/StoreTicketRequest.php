<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use App\Application\Rules\ExistsInCurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTicketRequest extends FormRequest
{
    public const TYPES = ['hardware', 'software', 'network', 'account_access', 'printer', 'email', 'security', 'other'];

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

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
            'employee_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('employees')],
            'type' => ['required', Rule::in(self::TYPES)],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }
}
