<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class AdjustStockRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'not_in:0'],
            'movement_type' => ['required', Rule::in(['inbound', 'outbound', 'adjustment'])],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('movement_type');
            $quantity = $this->input('quantity');

            if ($type === 'inbound' && $quantity !== null && $quantity < 0) {
                $validator->errors()->add('quantity', 'An inbound movement must have a positive quantity.');
            }

            if ($type === 'outbound' && $quantity !== null && $quantity > 0) {
                $validator->errors()->add('quantity', 'An outbound movement must have a negative quantity.');
            }
        });
    }
}
