<?php

namespace App\Http\Requests\Admin;

use App\Models\Subcategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'subcategory_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Subcategory::class, 'name')->where('category_id', $category->id),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $category = $this->route('category');
        $this->errorBag = 'subcategory-create-'.$category->id;

        $this->merge([
            'subcategory_name' => $this->string('subcategory_name')->trim()->toString(),
        ]);
    }
}
