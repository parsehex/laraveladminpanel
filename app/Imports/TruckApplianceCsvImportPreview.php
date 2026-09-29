<?php

namespace App\Imports;

class TruckApplianceCsvImportPreview
{
    /**
     * @param  list<array{row_number: int, unit_label: ?string, category: ?string, subcategory: ?string, brand: ?string, model: ?string, product_name: ?string, quantity: int, price: float, serial_number: ?string, receiving_condition: ?string, msrp: float, fuel_type: ?string, status: ?string, sold_price: ?float, sold_by: ?string, sold_at: ?string}>  $creates
     * @param  list<array{row_number: int, appliance_id: int, match_key: string, unit_label: ?string, serial_number: ?string, changes: list<array{field: string, label: string, from: string, to: string}>}>  $updates
     * @param  list<array{row_number: int, unit_label: ?string, serial_number: ?string, errors: list<string>}>  $errors
     * @param  list<string>  $newCategories
     * @param  list<string>  $newSubcategories
     * @param  list<string>  $newBrands
     * @param  list<string>  $newModels
     */
    public function __construct(
        public readonly array $creates,
        public readonly array $updates,
        public readonly array $errors,
        public readonly int $unchangedCount,
        public readonly array $newCategories,
        public readonly array $newSubcategories,
        public readonly array $newBrands,
        public readonly array $newModels,
    ) {}

    public function canConfirm(): bool
    {
        return $this->errors === [] && ($this->creates !== [] || $this->updates !== []);
    }

    public function createCount(): int
    {
        return count($this->creates);
    }

    public function updateCount(): int
    {
        return count($this->updates);
    }

    public function errorCount(): int
    {
        return count($this->errors);
    }

    public function hasSideEffects(): bool
    {
        return $this->newCategories !== []
            || $this->newSubcategories !== []
            || $this->newBrands !== []
            || $this->newModels !== [];
    }
}
