<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyCopyValue;
use App\Legacy\LegacyImportContext;
use Illuminate\Support\Facades\DB;

class AppliancePartsImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'appliance_parts';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('appliance_parts')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($context->dump->rows('appliance_parts') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $applianceId = $context->resolveApplianceId((int) $row['item_id']);
            if ($applianceId === null) {
                continue;
            }

            $attributes = [
                'truck_appliance_id' => $applianceId,
                'description' => $row['part_description'],
                'cost' => LegacyCopyValue::decimal($row['cost'] ?? null) ?? 0,
                'source' => $row['source'],
                'created_at' => $row['added_at'] ?? now(),
                'updated_at' => $row['added_at'] ?? now(),
            ];

            if ($context->dryRun) {
                continue;
            }

            $existingId = $context->idMap->get('appliance_parts', $legacyId);
            if ($existingId) {
                DB::table('appliance_parts')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $newId = DB::table('appliance_parts')->insertGetId($attributes);
                $context->idMap->remember('appliance_parts', $legacyId, $newId, $context->runId);
                $inserted++;
            }
        }

        $context->report->recordTable('appliance_parts', $read, $inserted, $updated);
    }
}
