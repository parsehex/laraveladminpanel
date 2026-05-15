<?php

namespace App\Http\Requests;

use App\Models\TruckAppliance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTruckApplianceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('appliance.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'truck_id' => ['required', 'exists:trucks,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'model_id' => ['nullable', 'exists:models,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'msrp' => ['required', 'numeric', 'min:0'],
            'receiving_condition' => ['nullable', Rule::in(TruckAppliance::RECEIVING_CONDITIONS)],
            'total_parts_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
