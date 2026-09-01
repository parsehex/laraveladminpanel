<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kits.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('kits', 'code')->ignore($this->route('kit'))],
            'name' => ['required', 'string', 'max:255'],
            'sop' => ['nullable', 'string'],
        ];
    }
}
