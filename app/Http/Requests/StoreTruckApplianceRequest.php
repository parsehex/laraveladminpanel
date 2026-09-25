<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\InventoryStatus;
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
            'category' => ['required', 'string', 'max:255'],
            'subcategory' => ['nullable', 'string', 'max:255'],
            'model_number' => [Rule::requiredIf(fn (): bool => ! $this->selectedCategoryIsFurniture()), 'nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'model_id' => ['nullable', 'exists:models,id'],
            'serial_number' => [Rule::requiredIf(fn (): bool => ! $this->selectedCategoryIsFurniture()), 'nullable', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'msrp' => ['nullable', 'numeric', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'max:255'],
            'receiving_condition' => ['required', Rule::in(TruckAppliance::RECEIVING_CONDITIONS)],
            'status' => ['nullable', Rule::in(InventoryStatus::activeNames())],
            'original_order_number' => ['nullable', 'string', 'max:255'],
            'return_reason' => ['nullable', 'string', 'max:255'],
            'return_problems' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function selectedCategoryIsFurniture(): bool
    {
        $name = trim((string) $this->input('category'));

        if ($name === '') {
            return false;
        }

        return Category::query()->where('name', $name)->first()?->isFurniture() ?? false;
    }
}
