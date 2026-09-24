<?php

namespace App\Legacy;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class LegacyBatchInserter
{
    private const CHUNK_SIZE = 100;

    /** @var list<array{table: string, legacy_table: string, legacy_id: int, attributes: array<string, mixed>, run_id: ?int}> */
    private array $pending = [];

    public function __construct(private readonly LegacyIdMapRepository $idMap) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function queue(string $table, string $legacyTable, int $legacyId, array $attributes, ?int $runId): void
    {
        $this->pending[] = [
            'table' => $table,
            'legacy_table' => $legacyTable,
            'legacy_id' => $legacyId,
            'attributes' => $attributes,
            'run_id' => $runId,
        ];

        if (count($this->pending) >= self::CHUNK_SIZE) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if ($this->pending === []) {
            $this->idMap->flush();

            return;
        }

        $groups = [];
        foreach ($this->pending as $row) {
            $groups[$row['table']][] = $row;
        }
        $this->pending = [];

        foreach ($groups as $table => $rows) {
            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                $ids = $this->insertReturningIds($table, array_column($chunk, 'attributes'));

                foreach ($chunk as $index => $row) {
                    $this->idMap->remember(
                        $row['legacy_table'],
                        $row['legacy_id'],
                        $ids[$index],
                        $row['run_id'],
                    );
                }
            }
        }

        $this->idMap->flush();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<int>
     */
    private function insertReturningIds(string $table, array $rows): array
    {
        $sorted = [];
        foreach ($rows as $row) {
            ksort($row);
            $sorted[] = $row;
        }

        $builder = DB::table($table);
        $grammar = $builder->getGrammar();
        $sql = $grammar->compileInsert($builder, $sorted).' returning '.$grammar->wrap('id');

        $inserted = $builder->getConnection()->select($sql, Arr::flatten($sorted, 1));

        return array_map(fn (object $row): int => (int) $row->id, $inserted);
    }
}
