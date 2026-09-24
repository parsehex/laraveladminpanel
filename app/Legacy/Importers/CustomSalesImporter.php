<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyCopyValue;
use App\Legacy\LegacyImportContext;
use Illuminate\Support\Facades\DB;

class CustomSalesImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'custom_sales';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('custom_sales')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($context->dump->rows('custom_sales') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $soldBy = (string) ($row['sold_by'] ?? '');

            $attributes = [
                'model_number' => $row['model_number'],
                'serial_number' => $row['serial_number'],
                'sold_price' => LegacyCopyValue::decimal($row['sold_price'] ?? null),
                'estimated_price' => LegacyCopyValue::decimal($row['estimated_price'] ?? null),
                'sold_by' => $soldBy,
                'created_by' => $context->users->resolve($soldBy, false),
                'created_at' => $row['sold_date'] ?? now(),
                'updated_at' => $row['sold_date'] ?? now(),
            ];

            if ($context->dryRun) {
                continue;
            }

            $existingId = $context->idMap->get('custom_sales', $legacyId);
            if ($existingId) {
                DB::table('custom_sales')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $context->queueInsert('custom_sales', 'custom_sales', $legacyId, $attributes);
                $inserted++;
            }
        }

        $context->report->recordTable('custom_sales', $read, $inserted, $updated);
    }
}
