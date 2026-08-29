<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyCopyValue;
use App\Legacy\LegacyImportContext;
use Illuminate\Support\Facades\DB;

class InventoryStatusHistoriesImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'inventory_status_history';
    }

    public function import(LegacyImportContext $context): void
    {
        $read = 0;
        $inserted = 0;
        $updated = 0;

        if ($context->dump->hasTable('inventory_status_history')) {
            foreach ($context->dump->rows('inventory_status_history') as $row) {
                $read++;
                $legacyId = (int) $row['id'];
                $applianceId = $context->resolveApplianceId((int) $row['item_id']);
                if ($applianceId === null) {
                    continue;
                }

                $legacyUserId = LegacyCopyValue::int($row['user_id'] ?? null);
                $userId = $context->resolveLegacyUserId($legacyUserId === 0 ? null : $legacyUserId);

                $attributes = [
                    'truck_appliance_id' => $applianceId,
                    'status' => $row['status'],
                    'notes' => $this->nullableString($row['notes'] ?? null),
                    'parts_ordered' => LegacyCopyValue::bool($row['parts_ordered'] ?? false),
                    'user_id' => $userId,
                    'created_at' => $row['timestamp'] ?? now(),
                    'updated_at' => $row['timestamp'] ?? now(),
                ];

                $this->persist($context, 'inventory_status_history', $legacyId, $attributes, $inserted, $updated);
            }
        }

        $read += $this->importTriageNotes($context, $inserted, $updated);

        $context->report->recordTable('inventory_status_histories', $read, $inserted, $updated);
    }

    private function importTriageNotes(LegacyImportContext $context, int &$inserted, int &$updated): int
    {
        if (! $context->dump->hasTable('truck_items')) {
            return 0;
        }

        $read = 0;

        foreach ($context->dump->rows('truck_items') as $row) {
            $hasTriage = ($row['triage_date'] ?? null) || ($row['initial_triage_condition'] ?? null)
                || ($row['essential_parts'] ?? null) || ($row['non_essential_parts'] ?? null)
                || LegacyCopyValue::bool($row['red_dot'] ?? false);

            if (! $hasTriage) {
                continue;
            }

            $read++;
            $legacyId = (int) $row['id'];
            $applianceId = $context->resolveApplianceId($legacyId);
            if ($applianceId === null) {
                continue;
            }

            $notes = $this->buildTriageNote($row);
            $userId = $context->users->resolve($row['triage_tech_id'] ?? null, false);

            $attributes = [
                'truck_appliance_id' => $applianceId,
                'status' => 'Triage',
                'notes' => $notes,
                'parts_ordered' => false,
                'user_id' => $userId,
                'created_at' => $row['triage_date'] ?? $row['added_at'] ?? now(),
                'updated_at' => $row['triage_date'] ?? $row['added_at'] ?? now(),
            ];

            $this->persist($context, 'triage_history', $legacyId, $attributes, $inserted, $updated);
        }

        return $read;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function buildTriageNote(array $row): string
    {
        $parts = [];

        if ($this->nullableString($row['initial_triage_condition'] ?? null)) {
            $parts[] = 'Initial condition: '.$row['initial_triage_condition'];
        }

        if ($this->nullableString($row['essential_parts'] ?? null)) {
            $parts[] = 'Essential parts: '.$row['essential_parts'];
        }

        if ($this->nullableString($row['non_essential_parts'] ?? null)) {
            $parts[] = 'Non-essential parts: '.$row['non_essential_parts'];
        }

        if (LegacyCopyValue::bool($row['red_dot'] ?? false)) {
            $parts[] = 'Red dot: complete salvage (parts only)';
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function persist(
        LegacyImportContext $context,
        string $legacyTable,
        int $legacyId,
        array $attributes,
        int &$inserted,
        int &$updated,
    ): void {
        if ($context->dryRun) {
            return;
        }

        $existingId = $context->idMap->get($legacyTable, $legacyId);
        if ($existingId) {
            DB::table('inventory_status_histories')->where('id', $existingId)->update($attributes);
            $updated++;

            return;
        }

        $newId = DB::table('inventory_status_histories')->insertGetId($attributes);
        $context->idMap->remember($legacyTable, $legacyId, $newId, $context->runId);
        $inserted++;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
