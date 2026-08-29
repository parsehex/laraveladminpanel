<?php

namespace App\Legacy;

class LegacyImportReporter
{
    public function __construct(
        private readonly LegacyDumpReader $dump,
        private readonly LegacyPatchLoader $patchLoader,
    ) {}

    public function build(): LegacyImportReport
    {
        $report = new LegacyImportReport;
        $patches = [
            'users' => $this->patchLoader->load('users'),
            'categories' => $this->patchLoader->load('categories'),
            'models' => $this->patchLoader->load('models'),
            'testing' => $this->patchLoader->load('testing'),
        ];

        $userResolver = new LegacyUserResolver($patches['users']);
        $categoryResolver = new LegacyCategoryResolver($patches['categories']);

        $allUserKeys = $userResolver->keysNeedingPatch($this->dump);
        $missingUserPatches = array_values(array_filter(
            $allUserKeys,
            fn (string $key) => ! array_key_exists($key, $patches['users']),
        ));

        $unresolvedWithPatch = [];
        foreach ($allUserKeys as $key) {
            if (! array_key_exists($key, $patches['users'])) {
                continue;
            }

            try {
                $userResolver->resolve($key, true);
            } catch (\Throwable) {
                $unresolvedWithPatch[] = $key;
            }
        }

        $orphanModels = $this->orphanModelNumbers();
        $unknownCategories = array_merge(
            $categoryResolver->unknownValues($this->dump, 'truck_items', 'category'),
            $categoryResolver->unknownValues($this->dump, 'models', 'category'),
        );
        $unknownCategories = array_values(array_unique($unknownCategories));

        $report->section('dump', [
            'path' => $this->dump->path(),
            'sha256' => $this->dump->sha256(),
            'tables' => collect($this->dump->tables())
                ->mapWithKeys(fn (string $table) => [$table => $this->dump->count($table)])
                ->all(),
        ]);

        $report->section('users', [
            'keys_in_dump' => $allUserKeys,
            'missing_patches' => $missingUserPatches,
            'unresolved_with_patch' => $unresolvedWithPatch,
            'suggested_patches' => collect($missingUserPatches)
                ->mapWithKeys(fn (string $key) => [
                    $key => ['create_inactive' => [
                        'name' => $key,
                        'email' => strtolower($key).'@legacy-import.local',
                    ]],
                ])
                ->all(),
        ]);

        $report->section('categories', [
            'unknown_values' => $unknownCategories,
        ]);

        $report->section('models', [
            'orphan_model_numbers' => $orphanModels,
            'count' => count($orphanModels),
        ]);

        $report->section('photos', [
            'appliances_with_photos' => $this->countAppliancesWithPhotos(),
        ]);

        foreach ($missingUserPatches as $key) {
            $report->warn("User patch missing for [{$key}]");
        }

        foreach ($unknownCategories as $category) {
            $report->warn("Unknown category [{$category}]");
        }

        return $report;
    }

    /**
     * @return list<string>
     */
    private function orphanModelNumbers(): array
    {
        if (! $this->dump->hasTable('truck_items') || ! $this->dump->hasTable('models')) {
            return [];
        }

        $known = [];
        foreach ($this->dump->rows('models') as $row) {
            $known[strtolower((string) $row['model_number'])] = true;
        }

        $orphans = [];
        foreach ($this->dump->rows('truck_items') as $row) {
            $modelNumber = trim((string) ($row['model_number'] ?? ''));
            if ($modelNumber === '') {
                continue;
            }

            if (! isset($known[strtolower($modelNumber)])) {
                $orphans[$modelNumber] = true;
            }
        }

        return array_keys($orphans);
    }

    private function countAppliancesWithPhotos(): int
    {
        if (! $this->dump->hasTable('truck_items')) {
            return 0;
        }

        $count = 0;
        foreach ($this->dump->rows('truck_items') as $row) {
            $photos = $row['photos_json'] ?? null;
            if ($photos !== null && $photos !== '' && $photos !== '[]') {
                $count++;
            }
        }

        return $count;
    }
}
