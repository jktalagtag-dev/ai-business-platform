<?php

declare(strict_types=1);

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAiConversationRequest extends FormRequest
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
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'system_prompt' => ['sometimes', 'nullable', 'string', 'max:8000'],
            'model' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
