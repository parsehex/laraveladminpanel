<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('models.create') ?? false;
    }

    public function rules(): array
    {
        $msrp = number_format((float) $this->input('msrp', 0), 2, '.', '');

        return [
            'model_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('models', 'model_number')
                    ->where(fn ($query) => $query->where('msrp', $msrp)->whereNull('deleted_at')),
            ],
            'product_name' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'msrp' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
