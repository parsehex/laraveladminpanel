<?php

namespace App\Legacy;

class LegacyImportContext
{
    public function __construct(
        public readonly LegacyDumpReader $dump,
        public readonly LegacyPatchLoader $patchLoader,
        public readonly LegacyIdMapRepository $idMap,
        public readonly LegacyCategoryResolver $categories,
        public readonly LegacyUserResolver $users,
        public readonly LegacyImportReport $report,
        /** @var array<int, string> */
        public readonly array $legacyUsernamesById = [],
        public readonly bool $dryRun = false,
        public readonly bool $strict = true,
        public readonly ?int $runId = null,
        public readonly ?LegacyBatchInserter $inserts = null,
    ) {}

    /** @var array<string, int> */
    private array $modelIdsByNumber = [];

    public function rememberModelNumber(string $modelNumber, int $id): void
    {
        $key = strtolower(trim($modelNumber));
        if ($key === '') {
            return;
        }

        $this->modelIdsByNumber[$key] = $id;
    }

    public function modelIdForNumber(string $modelNumber): ?int
    {
        return $this->modelIdsByNumber[strtolower(trim($modelNumber))] ?? null;
    }

    /**
     * @param  array<string, mixed>  $patches
     */
    public static function make(
        LegacyDumpReader $dump,
        LegacyPatchLoader $patchLoader,
        LegacyIdMapRepository $idMap,
        array $patches,
        LegacyImportReport $report,
        bool $dryRun = false,
        bool $strict = true,
        ?int $runId = null,
    ): self {
        $legacyUsernamesById = [];
        if ($dump->hasTable('users')) {
            foreach ($dump->rows('users') as $row) {
                $legacyUsernamesById[(int) $row['id']] = (string) $row['username'];
            }
        }

        return new self(
            dump: $dump,
            patchLoader: $patchLoader,
            idMap: $idMap,
            categories: new LegacyCategoryResolver($patches['categories'] ?? []),
            users: new LegacyUserResolver($patches['users'] ?? []),
            report: $report,
            legacyUsernamesById: $legacyUsernamesById,
            dryRun: $dryRun,
            strict: $strict,
            runId: $runId,
            inserts: new LegacyBatchInserter($idMap),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function queueInsert(string $table, string $legacyTable, int $legacyId, array $attributes): void
    {
        $this->inserts?->queue($table, $legacyTable, $legacyId, $attributes, $this->runId);
    }

    public function flushInserts(): void
    {
        $this->inserts?->flush();
    }

    public function resolveLegacyUserId(?int $legacyUserId): ?int
    {
        if ($legacyUserId === null || $legacyUserId <= 0) {
            return null;
        }

        $username = $this->legacyUsernamesById[$legacyUserId] ?? null;
        if ($username === null) {
            return null;
        }

        return $this->users->resolve($username, $this->strict);
    }

    public function resolveApplianceId(int $legacyItemId): ?int
    {
        return $this->idMap->get('truck_items', $legacyItemId);
    }

    /**
     * @return array<string, mixed>
     */
    public function modelPatch(): array
    {
        return $this->patchLoader->load('models');
    }

    /**
     * @return array<string, mixed>
     */
    public function testingPatch(): array
    {
        return $this->patchLoader->load('testing');
    }
}
