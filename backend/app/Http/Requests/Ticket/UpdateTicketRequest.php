<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTicketRequest extends FormRequest
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
            'type' => ['required', Rule::in(StoreTicketRequest::TYPES)],
            'priority' => ['required', Rule::in(StoreTicketRequest::PRIORITIES)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'resolution_notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
