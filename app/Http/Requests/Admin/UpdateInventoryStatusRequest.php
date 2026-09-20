<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory-statuses.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'auto_location' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $autoLocation = $this->string('auto_location')->trim()->toString();

        $this->merge([
            'auto_location' => $autoLocation === '' ? null : $autoLocation,
        ]);
    }
}
