<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Application\Rules\UniqueInCurrentTenant;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePositionRequest extends FormRequest
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
            'title' => [
                'required', 'string', 'max:255',
                new UniqueInCurrentTenant('positions', 'title', $this->route('position')),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
