<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyCopyValue;
use App\Legacy\LegacyImportContext;
use App\Legacy\LegacyText;
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
        $skipped = 0;
        $cleaned = 0;

        foreach ($context->dump->rows('parts') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $part = LegacyText::cleanPart(
                $row['part_number'] ?? null,
                $row['product_name'] ?? null,
                $row['cross_reference'] ?? null,
            );
            if ($part['part_number'] === '') {
                $skipped++;
                $context->report->warn("Skipped part {$legacyId}: empty part number after cleanup");

                continue;
            }
            if ($part['changed']) {
                $cleaned++;
            }

            $attributes = [
                'part_number' => $part['part_number'],
                'product_name' => $part['product_name'],
                'model_compatibility' => LegacyText::plain($row['model_compatibility'] ?? null),
                'total_stock' => LegacyCopyValue::int($row['total_stock'] ?? null) ?? 0,
                'retail_price' => LegacyCopyValue::decimal($row['retail_price'] ?? null) ?? 0,
                'your_price' => LegacyCopyValue::decimal($row['your_price'] ?? null) ?? 0,
                'cross_reference' => $part['cross_reference'],
                'diagram_name' => LegacyText::plain($row['diagram_name'] ?? null),
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
                $context->queueInsert('parts', 'parts', $legacyId, $attributes);
                $inserted++;
            }
        }

        $context->report->recordTable('parts', $read, $inserted, $updated, $skipped);
        $context->report->section('parts_cleanup', ['rewritten' => $cleaned]);
    }
}
