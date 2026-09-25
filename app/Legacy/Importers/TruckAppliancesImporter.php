<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyCopyValue;
use App\Legacy\LegacyImportContext;
use App\Legacy\LegacyText;
use Illuminate\Support\Facades\DB;

class TruckAppliancesImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'truck_items';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('truck_items')) {
            return;
        }

        $modelNumbersByLegacyId = $this->modelNumbersIndex($context);
        $read = 0;
        $inserted = 0;
        $updated = 0;
        $orphanModels = [];

        foreach ($context->dump->rows('truck_items') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $truckId = $context->idMap->get('trucks', (int) $row['truck_id']);
            if ($truckId === null) {
                $context->report->warn("Skipped truck_item {$legacyId}: missing truck map");

                continue;
            }

            $modelId = $this->resolveModelId($context, $row, $modelNumbersByLegacyId);
            if ($modelId === null && trim((string) ($row['model_number'] ?? '')) !== '') {
                $orphanModels[(string) $row['model_number']] = true;
            }

            $attributes = [
                'truck_id' => $truckId,
                'unit_label' => $row['unit_label'],
                'category_id' => $context->categories->resolve((string) $row['category']),
                'subcategory' => $this->nullableString($row['subcategory'] ?? null),
                'model_id' => $modelId,
                'serial_number' => $row['serial_number'],
                'brand' => LegacyText::plain($row['brand'] ?? null),
                'product_name' => LegacyText::plain($row['product_name'] ?? null),
                'quantity' => LegacyCopyValue::int($row['quantity'] ?? null) ?? 1,
                'price' => LegacyCopyValue::decimal($row['price'] ?? null) ?? 0,
                'msrp' => LegacyCopyValue::decimal($row['msrp'] ?? null),
                'fuel_type' => $this->nullableString($row['fuel_type'] ?? null),
                'receiving_condition' => $row['receiving_condition'],
                'status' => $row['current_status'],
                'location' => $this->nullableString($row['location'] ?? null),
                'sold_price' => LegacyCopyValue::decimal($row['sold_price'] ?? null),
                'sold_by' => $this->nullableString($row['sold_by'] ?? null),
                'sold_at' => $row['sold_date'],
                'photos' => json_encode($this->decodePhotos($row['photos_json'] ?? null)),
                'original_order_number' => $this->nullableString($row['original_order_number'] ?? null),
                'return_reason' => $this->nullableString($row['return_reason'] ?? null),
                'return_problems' => $this->nullableString($row['return_problems'] ?? null),
                'created_at' => $row['added_at'] ?? now(),
                'updated_at' => $row['added_at'] ?? now(),
            ];

            if ($context->dryRun) {
                continue;
            }

            $existingId = $context->idMap->get('truck_items', $legacyId);
            if ($existingId) {
                DB::table('truck_appliances')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $context->queueInsert('truck_appliances', 'truck_items', $legacyId, array_merge($attributes, [
                    'id' => $legacyId,
                ]));
                $inserted++;
            }
        }

        if (! $context->dryRun) {
            $context->flushInserts();
            $this->syncIdSequence();
        }

        $context->report->section('orphan_model_numbers', array_keys($orphanModels));
        $context->report->recordTable('truck_appliances', $read, $inserted, $updated);
    }

    private function syncIdSequence(): void
    {
        DB::statement(
            "SELECT setval(pg_get_serial_sequence('truck_appliances', 'id'), COALESCE((SELECT MAX(id) FROM truck_appliances), 1), true)"
        );
    }

    /**
     * @return array<string, int>
     */
    private function modelNumbersIndex(LegacyImportContext $context): array
    {
        $index = [];

        if (! $context->dump->hasTable('models')) {
            return $index;
        }

        foreach ($context->dump->rows('models') as $row) {
            $legacyModelId = (int) $row['id'];
            $newId = $context->idMap->get('models', $legacyModelId);
            $modelNumber = strtolower(trim((string) $row['model_number']));
            if ($newId !== null && $modelNumber !== '') {
                $index[$modelNumber] = $newId;
            }
        }

        return $index;
    }

    /**
     * @param  array<string, int>  $modelNumbersByLegacyId
     */
    private function resolveModelId(LegacyImportContext $context, array $row, array $modelNumbersByLegacyId): ?int
    {
        $modelNumber = trim((string) ($row['model_number'] ?? ''));
        if ($modelNumber === '') {
            return null;
        }

        return $modelNumbersByLegacyId[strtolower($modelNumber)] ?? null;
    }

    /**
     * @return list<string>
     */
    private function decodePhotos(mixed $raw): array
    {
        if ($raw === null || $raw === '' || $raw === '[]') {
            return [];
        }

        $json = str_replace('\\/', '/', (string) $raw);
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($value) => is_string($value) && $value !== ''));
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
