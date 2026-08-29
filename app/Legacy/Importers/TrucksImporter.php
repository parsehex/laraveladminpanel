<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyCopyValue;
use App\Legacy\LegacyImportContext;
use Illuminate\Support\Facades\DB;

class TrucksImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'trucks';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('trucks')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($context->dump->rows('trucks') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $attributes = [
                'name' => $row['truck_name'],
                'notes' => $row['notes'],
                'cost_of_truck' => LegacyCopyValue::decimal($row['truck_cost'] ?? null) ?? 0,
                'units_on_truck' => LegacyCopyValue::int($row['units'] ?? null) ?? 0,
                'arrival_date' => $row['arrival_date'],
                'status' => 'active',
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['created_at'] ?? now(),
            ];

            $existingId = $context->idMap->get('trucks', $legacyId);
            if ($context->dryRun) {
                continue;
            }

            if ($existingId) {
                DB::table('trucks')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $newId = DB::table('trucks')->insertGetId($attributes);
                $context->idMap->remember('trucks', $legacyId, $newId, $context->runId);
                $inserted++;
            }
        }

        $context->report->recordTable('trucks', $read, $inserted, $updated);
    }
}
