<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ApplyInventoryStatusAutoLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory-statuses.manage') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $status = $this->route('inventoryStatus');

                if (! $status instanceof InventoryStatus) {
                    return;
                }

                if ($status->auto_location === null || $status->auto_location === '') {
                    $validator->errors()->add(
                        'status',
                        __('Set an auto-location for this status before applying it to items.'),
                    );
                }
            },
        ];
    }
}
