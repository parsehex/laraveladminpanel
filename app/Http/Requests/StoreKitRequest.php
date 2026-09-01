<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kits.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:kits,code'],
            'name' => ['required', 'string', 'max:255'],
            'sop' => ['nullable', 'string'],
            'amazon_min_level' => ['nullable', 'integer', 'min:0'],
            'shopify_min_level' => ['nullable', 'integer', 'min:0'],
            'part_name' => ['array'],
            'part_name.*' => ['nullable', 'string', 'max:255'],
            'quantity_per_kit' => ['array'],
            'quantity_per_kit.*' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
