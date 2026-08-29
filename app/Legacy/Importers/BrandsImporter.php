<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyImportContext;
use App\Models\Brand;

class BrandsImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'brands';
    }

    public function import(LegacyImportContext $context): void
    {
        $brands = [];

        foreach (['models', 'truck_items'] as $table) {
            if (! $context->dump->hasTable($table)) {
                continue;
            }

            foreach ($context->dump->rows($table) as $row) {
                $brand = trim((string) ($row['brand'] ?? ''));
                if ($brand !== '') {
                    $brands[$brand] = true;
                }
            }
        }

        $read = count($brands);
        $inserted = 0;
        $updated = 0;

        foreach (array_keys($brands) as $name) {
            if ($context->dryRun) {
                continue;
            }

            $existing = Brand::query()->where('name', $name)->first();
            if ($existing) {
                $updated++;

                continue;
            }

            Brand::query()->create([
                'name' => $name,
                'status' => 1,
            ]);
            $inserted++;
        }

        $context->report->recordTable('brands', $read, $inserted, $updated);
    }
}
