<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyImportContext;
use App\Legacy\LegacyText;
use Illuminate\Support\Facades\DB;

class ModelsImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'models';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('models')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;
        $modelPatches = $context->modelPatch();

        foreach ($context->dump->rows('models') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $categoryId = $context->categories->resolve((string) $row['category']);
            $variations = $this->decodeJson($row['variations'] ?? null);

            $attributes = [
                'model_number' => $row['model_number'],
                'product_name' => $this->productName($row['product_name'] ?? null),
                'category_id' => $categoryId,
                'brand' => LegacyText::plain($row['brand'] ?? null),
                'msrp' => $row['msrp'] !== null ? (string) $row['msrp'] : null,
                'variations' => $variations !== null ? json_encode($variations) : null,
                'status' => 1,
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ];

            $existingId = $context->idMap->get('models', $legacyId);
            if ($context->dryRun) {
                continue;
            }

            if ($existingId) {
                DB::table('models')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $newId = DB::table('models')->insertGetId($attributes);
                $context->idMap->remember('models', $legacyId, $newId, $context->runId);
                $inserted++;
            }
        }

        $this->importStubsFromTruckItems($context, $modelPatches, $inserted);

        $context->report->recordTable('models', $read, $inserted, $updated);
    }

    /**
     * @param  array<string, mixed>  $modelPatches
     */
    private function importStubsFromTruckItems(LegacyImportContext $context, array $modelPatches, int &$inserted): void
    {
        if (! $context->dump->hasTable('truck_items')) {
            return;
        }

        $knownNumbers = [];
        foreach ($context->dump->rows('models') as $row) {
            $knownNumbers[strtolower((string) $row['model_number'])] = true;
        }

        foreach ($context->dump->rows('truck_items') as $row) {
            $modelNumber = trim((string) ($row['model_number'] ?? ''));
            if ($modelNumber === '') {
                continue;
            }

            $key = strtolower($modelNumber);
            if (isset($knownNumbers[$key])) {
                continue;
            }

            $patch = $modelPatches[$modelNumber] ?? $modelPatches[$key] ?? null;
            if ($patch !== 'stub') {
                continue;
            }

            if ($context->dryRun) {
                continue;
            }

            $categoryId = $context->categories->resolve((string) $row['category']);
            $newId = DB::table('models')->insertGetId([
                'model_number' => $modelNumber,
                'product_name' => $this->productName($row['product_name'] ?? null),
                'category_id' => $categoryId,
                'brand' => LegacyText::plain($row['brand'] ?? null),
                'msrp' => $row['msrp'] !== null ? (string) $row['msrp'] : null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $knownNumbers[$key] = true;
            $inserted++;
            $context->report->warn("Created stub model for orphan model_number [{$modelNumber}] as id {$newId}");
        }
    }

    private function productName(mixed $value): ?string
    {
        $name = LegacyText::plain($value);
        if ($name === null) {
            return null;
        }

        return str_replace('""', '"', $name);
    }

    private function decodeJson(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
