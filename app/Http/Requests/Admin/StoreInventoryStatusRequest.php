<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory-statuses.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(InventoryStatus::class, 'name')],
            'auto_location' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->string('name')->trim()->toString();
        $autoLocation = $this->string('auto_location')->trim()->toString();

        $this->merge([
            'name' => $name,
            'auto_location' => $autoLocation === '' ? null : $autoLocation,
        ]);
    }
}
