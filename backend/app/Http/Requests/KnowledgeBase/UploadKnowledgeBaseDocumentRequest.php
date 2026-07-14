<?php

declare(strict_types=1);

namespace App\Http\Requests\KnowledgeBase;

use Illuminate\Foundation\Http\FormRequest;

final class UploadKnowledgeBaseDocumentRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:'.(int) config('knowledge_base.max_upload_size_kb'),
            ],
        ];
    }
}
