<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyImportContext;
use Illuminate\Support\Facades\DB;

class ModelPartsImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'model_parts';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('model_parts')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($context->dump->rows('model_parts') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $modelId = $context->idMap->get('models', (int) $row['model_id']);
            $partId = $context->idMap->get('parts', (int) $row['part_id']);

            if ($modelId === null || $partId === null) {
                $context->report->warn("Skipped model_parts {$legacyId}: missing mapped model or part");

                continue;
            }

            $attributes = [
                'model_id' => $modelId,
                'part_id' => $partId,
                'variation' => $row['variation'] ?? 'default',
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['created_at'] ?? now(),
            ];

            $existingId = $context->idMap->get('model_parts', $legacyId);
            if ($context->dryRun) {
                continue;
            }

            if ($existingId) {
                DB::table('model_parts')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $newId = DB::table('model_parts')->insertGetId($attributes);
                $context->idMap->remember('model_parts', $legacyId, $newId, $context->runId);
                $inserted++;
            }
        }

        $context->report->recordTable('model_parts', $read, $inserted, $updated);
    }
}
