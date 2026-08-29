<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyImportContext;
use App\Models\Subcategory;

class SubcategoriesImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'subcategories';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('subcategories')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($context->dump->rows('subcategories') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $categoryId = $context->categories->resolve((string) $row['category']);
            if ($categoryId === null) {
                $context->report->warn("Skipped subcategory {$legacyId}: unknown category [{$row['category']}]");

                continue;
            }

            $attributes = [
                'category_id' => $categoryId,
                'name' => $row['subcategory'],
                'status' => 1,
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['created_at'] ?? now(),
            ];

            $existingId = $context->idMap->get('subcategories', $legacyId);
            if ($context->dryRun) {
                continue;
            }

            if ($existingId) {
                Subcategory::query()->whereKey($existingId)->update($attributes);
                $updated++;
            } else {
                $newId = Subcategory::query()->insertGetId($attributes);
                $context->idMap->remember('subcategories', $legacyId, $newId, $context->runId);
                $inserted++;
            }
        }

        $context->report->recordTable('subcategories', $read, $inserted, $updated);
    }
}
