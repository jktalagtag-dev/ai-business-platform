<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Application\Rules\ExistsInCurrentTenant;
use App\Application\Rules\UniqueInCurrentTenant;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
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
            'sku' => [
                'required', 'string', 'max:100',
                new UniqueInCurrentTenant('products', 'sku', $this->route('product')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('product_categories')],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
