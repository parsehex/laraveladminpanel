<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyImportContext;
use App\Models\TestingFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestingResultsImporter implements LegacyTableImporter
{
    public function legacyTable(): string
    {
        return 'testing_results';
    }

    public function import(LegacyImportContext $context): void
    {
        if (! $context->dump->hasTable('testing_results')) {
            return;
        }

        $read = 0;
        $inserted = 0;
        $updated = 0;
        $testingPatches = $context->testingPatch();
        $itemsByLegacyId = $this->truckItemsById($context);

        foreach ($context->dump->rows('testing_results') as $row) {
            $read++;
            $legacyId = (int) $row['id'];
            $patchKey = 'testing_results:'.$legacyId;
            $patch = $testingPatches[$patchKey] ?? null;

            $answers = json_decode((string) ($row['results'] ?? '{}'), true);
            if (! is_array($answers)) {
                $answers = [];
            }

            $answerCount = count($answers);
            $target = $patch['target'] ?? ($answerCount <= 4 ? 'repair_results' : 'testing_results');

            $legacyItemId = (int) $row['item_id'];
            $applianceId = $context->resolveApplianceId($legacyItemId);
            if ($applianceId === null) {
                continue;
            }

            $item = $itemsByLegacyId[$legacyItemId] ?? null;
            $flowSlug = $context->categories->slugFromCategoryName($item['category'] ?? null);
            $flow = $flowSlug ? TestingFlow::query()->where('slug', $flowSlug)->first() : null;
            $flowSnapshot = $flow ? [
                'slug' => $flow->slug,
                'version' => $flow->version,
                'name' => $flow->name,
                'start' => $flow->start,
                'steps' => $flow->steps,
            ] : ['slug' => $flowSlug, 'version' => 1, 'steps' => []];

            $completedAt = $row['completed_at'] ?? now();
            $resultId = $applianceId.'-'.date('YmdHis', strtotime((string) $completedAt)).'-'.Str::lower(Str::random(4));
            $userName = (string) ($row['completed_by'] ?? '');
            $userId = $context->users->resolve($userName, false);

            if ($target === 'repair_results') {
                $attributes = [
                    'result_id' => $resultId,
                    'truck_appliance_id' => $applianceId,
                    'type' => 'reevaluation',
                    'resulting_status' => 'Show Room',
                    'answers' => json_encode($answers),
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'completed_at' => $completedAt,
                    'created_at' => $completedAt,
                    'updated_at' => $completedAt,
                ];

                if ($context->dryRun) {
                    continue;
                }

                $existingId = $context->idMap->get('testing_results', $legacyId);
                if ($existingId) {
                    DB::table('repair_results')->where('id', $existingId)->update($attributes);
                    $updated++;
                } else {
                    $context->queueInsert('repair_results', 'testing_results', $legacyId, $attributes);
                    $inserted++;
                }

                continue;
            }

            $attributes = [
                'result_id' => $resultId,
                'truck_appliance_id' => $applianceId,
                'flow_slug' => $flowSlug,
                'flow_version' => $flow?->version ?? 1,
                'resulting_status' => $item['current_status'] ?? 'Repair',
                'answers' => json_encode($answers),
                'flow_snapshot' => json_encode($flowSnapshot),
                'user_id' => $userId,
                'user_name' => $userName,
                'completed_at' => $completedAt,
                'created_at' => $completedAt,
                'updated_at' => $completedAt,
            ];

            if ($context->dryRun) {
                continue;
            }

            $existingId = $context->idMap->get('testing_results', $legacyId);
            if ($existingId) {
                DB::table('testing_results')->where('id', $existingId)->update($attributes);
                $updated++;
            } else {
                $context->queueInsert('testing_results', 'testing_results', $legacyId, $attributes);
                $inserted++;
            }
        }

        $context->report->recordTable('testing_results', $read, $inserted, $updated);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function truckItemsById(LegacyImportContext $context): array
    {
        $items = [];
        if (! $context->dump->hasTable('truck_items')) {
            return $items;
        }

        foreach ($context->dump->rows('truck_items') as $row) {
            $items[(int) $row['id']] = $row;
        }

        return $items;
    }
}
