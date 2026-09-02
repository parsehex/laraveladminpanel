<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyCopyValue;
use App\Legacy\LegacyImportContext;
use Illuminate\Support\Facades\DB;

class PartsImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'parts';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('parts')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($context->dump->rows('parts') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $attributes = [
                'part_number' => $row['part_number'],
                'product_name' => $row['product_name'],
                'model_compatibility' => $row['model_compatibility'],
                'total_stock' => LegacyCopyValue::int($row['total_stock'] ?? null) ?? 0,
                'retail_price' => LegacyCopyValue::decimal($row['retail_price'] ?? null) ?? 0,
                'your_price' => LegacyCopyValue::decimal($row['your_price'] ?? null) ?? 0,
                'cross_reference' => $row['cross_reference'],
                'diagram_name' => $row['diagram_name'],
                'image_url' => $row['image_url'],
                'make' => $row['make'],
                'item' => $row['item'],
                'created_at' => $row['updated_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ];

            $existingId = $context->idMap->get('parts', $legacyId);
            if ($context->dryRun) {
                continue;
            }

            if ($existingId) {
                DB::table('parts')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $newId = DB::table('parts')->insertGetId($attributes);
                $context->idMap->remember('parts', $legacyId, $newId, $context->runId);
                $inserted++;
            }
        }

        $context->report->recordTable('parts', $read, $inserted, $updated);
    }
}
