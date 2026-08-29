<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyImportContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RepairDiagnosesImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'repair_diagnoses';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('repair_diagnoses')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($context->dump->rows('repair_diagnoses') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $applianceId = $context->resolveApplianceId((int) $row['item_id']);
            if ($applianceId === null) {
                continue;
            }

            $userName = (string) ($row['created_by'] ?? '');
            $attributes = [
                'uuid' => (string) Str::uuid(),
                'truck_appliance_id' => $applianceId,
                'diagnosis' => $row['diagnosis'],
                'user_id' => $context->users->resolve($userName, false),
                'user_name' => $userName,
                'created_at' => $row['created_at'] ?? now(),
            ];

            $existingId = $context->idMap->get('repair_diagnoses', $legacyId);
            if ($context->dryRun) {
                continue;
            }

            if ($existingId) {
                unset($attributes['uuid']);
                DB::table('repair_diagnoses')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $newId = DB::table('repair_diagnoses')->insertGetId($attributes);
                $context->idMap->remember('repair_diagnoses', $legacyId, $newId, $context->runId);
                $inserted++;
            }
        }

        $context->report->recordTable('repair_diagnoses', $read, $inserted, $updated);
    }
}
