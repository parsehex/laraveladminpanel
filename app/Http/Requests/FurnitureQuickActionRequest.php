<?php

namespace App\Http\Requests;

use App\Enums\FurnitureQuickAction;
use App\Models\InventoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FurnitureQuickActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('appliance.edit') ?? false;
    }

    /**
     * Statuses the change-status action can move units to; selling has its own action so a price is captured.
     *
     * @return list<string>
     */
    public static function targetStatuses(): array
    {
        return array_values(array_filter(
            InventoryStatus::activeNames(),
            fn (string $status) => $status !== 'Sold',
        ));
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(FurnitureQuickAction::class)],
            'count' => ['required', 'integer', 'min:1', 'max:1000'],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer'],
            'override' => ['nullable', 'boolean'],
            'status' => [
                Rule::requiredIf(fn (): bool => $this->quickAction() === FurnitureQuickAction::ChangeStatus),
                'nullable',
                Rule::in(self::targetStatuses()),
            ],
            'sold_price' => [
                Rule::requiredIf(fn (): bool => $this->quickAction() === FurnitureQuickAction::Sell),
                'nullable',
                'numeric',
                'min:0',
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sold_price.required' => __('Enter the sale price for one unit.'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $checkedCount = count(array_unique($this->input('unit_ids', [])));

                if ($checkedCount > (int) $this->input('count')) {
                    $validator->errors()->add('count', __('The count can’t be lower than the :checked checked units.', [
                        'checked' => $checkedCount,
                    ]));
                }
            },
        ];
    }

    public function quickAction(): ?FurnitureQuickAction
    {
        return FurnitureQuickAction::tryFrom((string) $this->input('action'));
    }

    /**
     * @return list<int>
     */
    public function checkedUnitIds(): array
    {
        return array_values(array_map('intval', $this->validated('unit_ids') ?? []));
    }
}
