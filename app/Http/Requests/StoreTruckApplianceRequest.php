<?php

namespace App\Http\Requests;

use App\Models\TruckAppliance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTruckApplianceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('appliance.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'truck_id' => ['required', 'exists:trucks,id'],
            'unit_label' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'model_id' => ['required', 'exists:models,id'],
            'serial_number' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'msrp' => ['required', 'numeric', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'max:255'],
            'receiving_condition' => ['required', Rule::in(TruckAppliance::RECEIVING_CONDITIONS)],
            'status' => ['nullable', Rule::in(\App\Http\Controllers\Admin\InventoryController::STATUSES)],
            'total_parts_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
