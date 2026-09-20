<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RenameInventoryLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory-locations.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'string', 'max:255'],
            'to' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->string('from')->toString(),
            'to' => $this->string('to')->trim()->toString(),
        ]);
    }
}
