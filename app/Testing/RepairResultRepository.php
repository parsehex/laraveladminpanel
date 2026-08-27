<?php

namespace App\Testing;

use App\Models\RepairResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RepairResultRepository
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): string
    {
        $applianceId = (int) ($payload['appliance_id'] ?? 0);
        $resultId = $this->makeResultId($applianceId);

        RepairResult::query()->create([
            'result_id' => $resultId,
            'truck_appliance_id' => $applianceId,
            'type' => $payload['type'] ?? 'reevaluation',
            'source_testing_result_id' => $payload['source_testing_result_id'] ?? null,
            'source_flow_slug' => $payload['source_flow_slug'] ?? null,
            'source_flow_version' => isset($payload['source_flow_version']) ? (int) $payload['source_flow_version'] : null,
            'resulting_status' => (string) ($payload['resulting_status'] ?? ''),
            'answers' => is_array($payload['answers'] ?? null) ? $payload['answers'] : [],
            'failed_steps_snapshot' => is_array($payload['failed_steps_snapshot'] ?? null) ? $payload['failed_steps_snapshot'] : [],
            'user_id' => $payload['user_id'] ?? null,
            'user_name' => $payload['user_name'] ?? null,
            'completed_at' => $this->parseTimestamp($payload['completed_at'] ?? null) ?? now(),
        ]);

        return $resultId;
    }

    /**
     * @return list<array{result_id: string, appliance_id: int, resulting_status: string, completed_at: ?string, user_name: ?string, source_testing_result_id: ?string}>
     */
    public function listForAppliance(int $applianceId): array
    {
        return RepairResult::query()
            ->where('truck_appliance_id', $applianceId)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (RepairResult $result) => [
                'result_id' => $result->result_id,
                'appliance_id' => $applianceId,
                'resulting_status' => (string) $result->resulting_status,
                'completed_at' => optional($result->completed_at)?->utc()->toIso8601String(),
                'user_name' => $result->user_name,
                'source_testing_result_id' => $result->source_testing_result_id,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $resultId): ?array
    {
        if (! $this->isValidResultId($resultId)) {
            return null;
        }

        $result = RepairResult::query()->where('result_id', $resultId)->first();

        return $result?->toPayload();
    }

    public function belongsToAppliance(string $resultId, int $applianceId): bool
    {
        if (! $this->isValidResultId($resultId)) {
            return false;
        }

        return RepairResult::query()
            ->where('result_id', $resultId)
            ->where('truck_appliance_id', $applianceId)
            ->exists();
    }

    /**
     * @param  iterable<\App\Models\InventoryStatusHistory>  $histories
     * @return array<int, string>
     */
    public function mapResultLinksToRepairHistories(int $applianceId, iterable $histories): array
    {
        $historiesAsc = collect($histories)->sortBy('created_at')->values();
        $links = [];

        foreach ($this->listForAppliance($applianceId) as $summary) {
            $resultId = (string) ($summary['result_id'] ?? '');
            if ($resultId === '') {
                continue;
            }

            $completedAt = null;
            try {
                $completedAt = isset($summary['completed_at'])
                    ? Carbon::parse($summary['completed_at'])
                    : null;
            } catch (\Throwable) {
                $completedAt = null;
            }

            $anchor = $completedAt;

            foreach ($historiesAsc as $history) {
                if ($history->repairResultId() === $resultId) {
                    $anchor = $history->created_at ?? $anchor;
                    break;
                }
            }

            if ($anchor === null) {
                continue;
            }

            $repairHistory = $historiesAsc
                ->filter(function ($history) use ($anchor) {
                    if ($history->status !== 'Repair') {
                        return false;
                    }

                    $at = $history->created_at;

                    return $at !== null && $at->lte($anchor);
                })
                ->sortByDesc('created_at')
                ->first();

            if ($repairHistory !== null && ! isset($links[$repairHistory->id])) {
                $links[$repairHistory->id] = $resultId;
            }
        }

        return $links;
    }

    public function isValidResultId(string $resultId): bool
    {
        return (bool) preg_match('/^\d+-\d{14}-[a-z0-9]{4}$/', $resultId);
    }

    private function makeResultId(int $applianceId): string
    {
        return $applianceId.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(4));
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
