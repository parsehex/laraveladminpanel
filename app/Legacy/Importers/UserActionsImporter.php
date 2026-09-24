<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyCopyValue;
use App\Legacy\LegacyImportContext;
use Illuminate\Support\Facades\DB;

class UserActionsImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'user_actions';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('user_actions')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;

        foreach ($context->dump->rows('user_actions') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $applianceId = $context->resolveApplianceId((int) ($row['item_id'] ?? 0));
            $userId = $context->resolveLegacyUserId(LegacyCopyValue::int($row['user_id'] ?? null));

            $extra = $row['extra'] ?? null;
            if (is_string($extra)) {
                $extra = json_decode($extra, true);
            }

            $attributes = [
                'username' => $row['username'],
                'user_id' => $userId,
                'action_type' => $row['action_type'],
                'item_id' => $applianceId,
                'extra' => $extra !== null ? json_encode($extra) : null,
                'created_at' => $row['created_at'] ?? now(),
            ];

            if ($context->dryRun) {
                continue;
            }

            $existingId = $context->idMap->get('user_actions', $legacyId);
            if ($existingId) {
                DB::table('user_actions')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $context->queueInsert('user_actions', 'user_actions', $legacyId, $attributes);
                $inserted++;
            }
        }

        $context->report->recordTable('user_actions', $read, $inserted, $updated);
    }
}
