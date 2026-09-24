<?php

namespace App\Legacy;

use Illuminate\Support\Facades\DB;

class LegacyIdMapRepository
{
    private const CHUNK_SIZE = 500;

    /** @var array<string, array<int, int>> */
    private array $maps = [];

    /** @var array<string, true> */
    private array $loaded = [];

    /** @var list<array<string, mixed>> */
    private array $pending = [];

    public function get(string $legacyTable, int $legacyId): ?int
    {
        $this->preload($legacyTable);

        return $this->maps[$legacyTable][$legacyId] ?? null;
    }

    public function remember(string $legacyTable, int $legacyId, int $newId, ?int $runId = null): void
    {
        $this->preload($legacyTable);
        $this->maps[$legacyTable][$legacyId] = $newId;
        $this->pending[] = [
            'legacy_table' => $legacyTable,
            'legacy_id' => $legacyId,
            'new_id' => $newId,
            'legacy_import_run_id' => $runId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function flush(): void
    {
        if ($this->pending === []) {
            return;
        }

        foreach (array_chunk($this->pending, self::CHUNK_SIZE) as $chunk) {
            DB::table('legacy_id_map')->insert($chunk);
        }

        $this->pending = [];
    }

    public function forgetTable(string $legacyTable): void
    {
        unset($this->maps[$legacyTable], $this->loaded[$legacyTable]);
        $this->pending = array_values(array_filter(
            $this->pending,
            fn (array $row): bool => $row['legacy_table'] !== $legacyTable,
        ));

        DB::table('legacy_id_map')->where('legacy_table', $legacyTable)->delete();
    }

    public function forgetAll(): void
    {
        $this->maps = [];
        $this->loaded = [];
        $this->pending = [];

        DB::table('legacy_id_map')->delete();
    }

    private function preload(string $legacyTable): void
    {
        if (isset($this->loaded[$legacyTable])) {
            return;
        }

        $this->maps[$legacyTable] ??= [];

        $rows = DB::table('legacy_id_map')
            ->where('legacy_table', $legacyTable)
            ->get(['legacy_id', 'new_id']);

        foreach ($rows as $row) {
            $this->maps[$legacyTable][(int) $row->legacy_id] = (int) $row->new_id;
        }

        $this->loaded[$legacyTable] = true;
    }
}
