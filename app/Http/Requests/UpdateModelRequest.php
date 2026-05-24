<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('models.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'model_number' => ['required', 'string', 'max:255', Rule::unique('models', 'model_number')->ignore($this->route('model'))],
            'product_name' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'msrp' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
