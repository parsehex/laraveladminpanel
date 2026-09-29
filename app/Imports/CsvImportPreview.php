<?php

namespace App\Imports;

class CsvImportPreview
{
    /**
     * @param  list<array<string, mixed>>  $creates
     * @param  list<array{row_number: int, match_key: string, changes: list<array{field: string, label: string, from: string, to: string}>}>  $updates
     * @param  list<array{row_number: int, match_key: ?string, errors: list<string>}>  $errors
     * @param  array<string, string>  $createColumns  field key => column header
     * @param  array<string, list<string>>  $sideEffects  section title => items
     * @param  list<array{row_number: int, match_key: string, effects: list<string>}>  $sideEffectRows
     */
    public function __construct(
        public readonly array $creates,
        public readonly array $updates,
        public readonly array $errors,
        public readonly int $unchangedCount,
        public readonly array $createColumns,
        public readonly array $sideEffects = [],
        public readonly array $sideEffectRows = [],
    ) {}

    public function canConfirm(): bool
    {
        return $this->errors === []
            && ($this->creates !== [] || $this->updates !== [] || $this->sideEffectRows !== []);
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

    public function sideEffectRowCount(): int
    {
        return count($this->sideEffectRows);
    }

    public function hasSideEffects(): bool
    {
        if ($this->sideEffectRows !== []) {
            return true;
        }

        foreach ($this->sideEffects as $items) {
            if ($items !== []) {
                return true;
            }
        }

        return false;
    }
}
