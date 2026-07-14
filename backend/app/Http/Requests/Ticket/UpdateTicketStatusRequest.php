<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTicketStatusRequest extends FormRequest
{
    /**
     * "closed" is deliberately excluded — POST /tickets/{id}/close is the
     * only path to that status, since it requires resolution_notes.
     */
    public const STATUSES = ['open', 'assigned', 'in_progress', 'waiting_for_user', 'resolved', 'cancelled'];

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
            'status' => ['required', Rule::in(self::STATUSES)],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
