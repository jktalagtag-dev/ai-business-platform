<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Application\Contracts\Repositories\Inventory\ProductCategoryRepositoryInterface;
use App\Application\Rules\ExistsInCurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'parent_category_id' => ['sometimes', 'nullable', 'string', new ExistsInCurrentTenant('product_categories')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parentId = $this->input('parent_category_id');
            $name = $this->input('name');

            if ($name && app(ProductCategoryRepositoryInterface::class)->nameExistsUnderParent($parentId, $name)) {
                $validator->errors()->add('name', 'A category with this name already exists under the selected parent.');
            }
        });
    }
}
