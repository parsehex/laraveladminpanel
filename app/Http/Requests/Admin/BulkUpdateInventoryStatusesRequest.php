<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkUpdateInventoryStatusesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory-statuses.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'statuses' => ['required', 'array', 'min:1'],
            'statuses.*.auto_location' => ['nullable', 'string', 'max:255'],
            'statuses.*.color' => ['required', 'hex_color'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $submittedIds = collect($this->input('statuses', []))
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->values();

            if ($submittedIds->isEmpty()) {
                $validator->errors()->add('statuses', __('Select at least one status to update.'));

                return;
            }

            $activeIds = InventoryStatus::query()
                ->active()
                ->whereIn('id', $submittedIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $unknown = $submittedIds->diff($activeIds);

            if ($unknown->isNotEmpty()) {
                $validator->errors()->add('statuses', __('One or more statuses could not be updated.'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $statuses = $this->input('statuses', []);

        if (! is_array($statuses)) {
            return;
        }

        $normalized = [];

        foreach ($statuses as $id => $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $autoLocation = trim((string) ($payload['auto_location'] ?? ''));
            $color = strtolower(ltrim(trim((string) ($payload['color'] ?? '')), '#'));

            $normalized[(string) $id] = [
                'auto_location' => $autoLocation === '' ? null : $autoLocation,
                'color' => $color === '' ? null : '#'.$color,
            ];
        }

        $this->merge([
            'statuses' => $normalized,
        ]);
    }
}
