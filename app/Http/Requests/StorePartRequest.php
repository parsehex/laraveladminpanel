<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('parts.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'part_number' => ['required', 'string', 'max:255', Rule::unique('parts', 'part_number')->ignore(null, 'id')],
            'product_name' => ['nullable', 'string', 'max:255'],
            'model_compatibility' => ['nullable', 'string', 'max:255'],
            'total_stock' => ['required', 'integer', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'your_price' => ['required', 'numeric', 'min:0'],
            'cross_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
