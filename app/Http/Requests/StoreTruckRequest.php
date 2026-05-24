<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTruckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('trucks.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'units_on_truck' => ['required', 'integer', 'min:0'],
            'cost_of_truck' => ['required', 'numeric', 'min:0'],
            'arrival_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'breakdown'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
