<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('models.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'model_number' => ['required', 'string', 'max:255', 'unique:models,model_number,NULL,id,deleted_at,NULL'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'msrp' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
