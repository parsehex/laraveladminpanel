<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ArchiveInventoryStatusRequest extends FormRequest
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

                if ($status->is_system) {
                    $validator->errors()->add('status', __('System statuses cannot be archived.'));

                    return;
                }

                if ($status->archived_at !== null) {
                    $validator->errors()->add('status', __('This status is already archived.'));

                    return;
                }

                if ($status->isInUse()) {
                    $validator->errors()->add('status', __('This status cannot be archived while items use it.'));
                }
            },
        ];
    }
}
