<?php

namespace App\Testing;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RepairResultRepository
{
    public function resultsPath(): string
    {
        return storage_path('app/repair-results');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): string
    {
        File::ensureDirectoryExists($this->resultsPath());

        $applianceId = (int) ($payload['appliance_id'] ?? 0);
        $stamp = now()->format('YmdHis');
        $resultId = $applianceId.'-'.$stamp.'-'.Str::lower(Str::random(4));
        $path = $this->resultsPath().'/'.$resultId.'.json';

        $payload['result_id'] = $resultId;
        $payload['type'] = $payload['type'] ?? 'reevaluation';
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $resultId;
    }

    /**
     * @return list<array{result_id: string, appliance_id: int, resulting_status: string, completed_at: ?string, user_name: ?string, source_testing_result_id: ?string}>
     */
    public function listForAppliance(int $applianceId): array
    {
        if (! File::isDirectory($this->resultsPath())) {
            return [];
        }

        return collect(File::files($this->resultsPath()))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->map(function ($file) use ($applianceId) {
                $resultId = $file->getFilenameWithoutExtension();
                if (! $this->isValidResultId($resultId)) {
                    return null;
                }

                $data = $this->decode($file->getPathname());
                if ($data === null || (int) ($data['appliance_id'] ?? 0) !== $applianceId) {
                    return null;
                }

                return [
                    'result_id' => (string) ($data['result_id'] ?? $resultId),
                    'appliance_id' => $applianceId,
                    'resulting_status' => (string) ($data['resulting_status'] ?? ''),
                    'completed_at' => $data['completed_at'] ?? null,
                    'user_name' => $data['user_name'] ?? null,
                    'source_testing_result_id' => $data['source_testing_result_id'] ?? null,
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $row) => $row['completed_at'] ?? '')
            ->values()
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

        $path = $this->resultsPath().'/'.$resultId.'.json';

        return File::exists($path) ? $this->decode($path) : null;
    }

    public function belongsToAppliance(string $resultId, int $applianceId): bool
    {
        $result = $this->get($resultId);

        return $result !== null && (int) ($result['appliance_id'] ?? 0) === $applianceId;
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

    /**
     * @return array<string, mixed>|null
     */
    private function decode(string $path): ?array
    {
        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : null;
    }
}
