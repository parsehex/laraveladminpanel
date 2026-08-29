<?php

namespace App\Legacy;

use Illuminate\Support\Facades\DB;

class LegacyIdMapRepository
{
    public function get(string $legacyTable, int $legacyId): ?int
    {
        $row = DB::table('legacy_id_map')
            ->where('legacy_table', $legacyTable)
            ->where('legacy_id', $legacyId)
            ->first();

        return $row ? (int) $row->new_id : null;
    }

    public function remember(string $legacyTable, int $legacyId, int $newId, ?int $runId = null): void
    {
        $existing = DB::table('legacy_id_map')
            ->where('legacy_table', $legacyTable)
            ->where('legacy_id', $legacyId)
            ->first();

        if ($existing) {
            DB::table('legacy_id_map')->where('id', $existing->id)->update([
                'new_id' => $newId,
                'legacy_import_run_id' => $runId,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('legacy_id_map')->insert([
            'legacy_table' => $legacyTable,
            'legacy_id' => $legacyId,
            'new_id' => $newId,
            'legacy_import_run_id' => $runId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function forgetTable(string $legacyTable): void
    {
        DB::table('legacy_id_map')->where('legacy_table', $legacyTable)->delete();
    }

    public function forgetAll(): void
    {
        DB::table('legacy_id_map')->delete();
    }
}
