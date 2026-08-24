<?php

namespace App\Testing;

use App\Models\TestingFlow;
use App\Models\TestingFlowVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TestingFlowRepository
{
    public function resultsPath(): string
    {
        return storage_path('app/testing-results');
    }

    /**
     * @return list<array{slug: string, name: string, version: int, updated_at: ?string, step_count: int}>
     */
    public function list(): array
    {
        return TestingFlow::query()
            ->orderBy('slug')
            ->get()
            ->map(fn (TestingFlow $flow) => [
                'slug' => $flow->slug,
                'name' => $flow->name,
                'version' => (int) $flow->version,
                'updated_at' => optional($flow->updated_at)?->utc()->toIso8601String(),
                'step_count' => count($flow->steps ?? []),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $slug): ?array
    {
        $slug = $this->normalizeSlug($slug);
        $flow = TestingFlow::query()->where('slug', $slug)->first();

        return $flow ? $this->modelToArray($flow) : null;
    }

    /**
     * @param  array<string, mixed>  $flow
     * @return array<string, mixed>
     */
    public function save(array $flow, bool $bumpVersion = true): array
    {
        $slug = $this->normalizeSlug((string) ($flow['slug'] ?? ''));
        if ($slug === '') {
            throw new InvalidArgumentException('Flow slug is required.');
        }

        $normalized = $this->normalizeFlow($flow, $slug);
        $errors = $this->validate($normalized);
        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        return DB::transaction(function () use ($slug, $normalized, $bumpVersion) {
            /** @var TestingFlow|null $existing */
            $existing = TestingFlow::query()->where('slug', $slug)->lockForUpdate()->first();

            if ($existing !== null && $bumpVersion) {
                TestingFlowVersion::query()->create([
                    'testing_flow_id' => $existing->id,
                    'version' => (int) $existing->version,
                    'name' => $existing->name,
                    'start' => $existing->start,
                    'steps' => $existing->steps,
                    'created_at' => now(),
                ]);
                $normalized['version'] = (int) $existing->version + 1;
            } else {
                $normalized['version'] = (int) ($normalized['version'] ?? ($existing->version ?? 1));
            }

            $model = TestingFlow::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $normalized['name'],
                    'version' => $normalized['version'],
                    'start' => $normalized['start'],
                    'steps' => $normalized['steps'],
                ]
            );

            return $this->modelToArray($model->fresh());
        });
    }

    public function delete(string $slug): bool
    {
        $slug = $this->normalizeSlug($slug);
        $flow = TestingFlow::query()->where('slug', $slug)->first();
        if ($flow === null) {
            return false;
        }

        return (bool) $flow->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeResult(array $payload): string
    {
        File::ensureDirectoryExists($this->resultsPath());

        $applianceId = (int) ($payload['appliance_id'] ?? 0);
        $stamp = now()->format('YmdHis');
        $resultId = $applianceId.'-'.$stamp.'-'.Str::lower(Str::random(4));
        $path = $this->resultsPath().'/'.$resultId.'.json';

        $payload['result_id'] = $resultId;
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $resultId;
    }

    /**
     * @return list<array{result_id: string, appliance_id: int, flow_slug: string, flow_version: int, resulting_status: string, completed_at: ?string, user_name: ?string}>
     */
    public function listResultsForAppliance(int $applianceId): array
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

                $data = $this->decodeResultFile($file->getPathname());
                if ($data === null || (int) ($data['appliance_id'] ?? 0) !== $applianceId) {
                    return null;
                }

                return [
                    'result_id' => (string) ($data['result_id'] ?? $resultId),
                    'appliance_id' => $applianceId,
                    'flow_slug' => (string) ($data['flow_slug'] ?? ''),
                    'flow_version' => (int) ($data['flow_version'] ?? 1),
                    'resulting_status' => (string) ($data['resulting_status'] ?? ''),
                    'completed_at' => $data['completed_at'] ?? null,
                    'user_name' => $data['user_name'] ?? null,
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
    public function getResult(string $resultId): ?array
    {
        if (! $this->isValidResultId($resultId)) {
            return null;
        }

        $path = $this->resultsPath().'/'.$resultId.'.json';
        if (! File::exists($path)) {
            return null;
        }

        $data = $this->decodeResultFile($path);

        return is_array($data) ? $data : null;
    }

    public function resultBelongsToAppliance(string $resultId, int $applianceId): bool
    {
        $result = $this->getResult($resultId);

        return $result !== null && (int) ($result['appliance_id'] ?? 0) === $applianceId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestResultForAppliance(int $applianceId): ?array
    {
        $latest = null;
        $latestAt = null;

        if (! File::isDirectory($this->resultsPath())) {
            return null;
        }

        foreach (File::files($this->resultsPath()) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $data = $this->decodeResultFile($file->getPathname());
            if ($data === null || (int) ($data['appliance_id'] ?? 0) !== $applianceId) {
                continue;
            }

            $completedAt = $data['completed_at'] ?? null;
            if ($latest === null || ($completedAt !== null && ($latestAt === null || $completedAt > $latestAt))) {
                $latest = $data;
                $latestAt = $completedAt;
            }
        }

        return $latest;
    }

    /**
     * Map testing result ids onto Testing status-history rows (not the completion row).
     *
     * @param  iterable<\App\Models\InventoryStatusHistory>  $histories
     * @return array<int, string> history id => result id
     */
    public function mapResultLinksToTestingHistories(int $applianceId, iterable $histories): array
    {
        $historiesAsc = collect($histories)->sortBy('created_at')->values();
        $links = [];

        foreach ($this->listResultsForAppliance($applianceId) as $summary) {
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
                if ($history->testingResultId() === $resultId) {
                    $anchor = $history->created_at ?? $anchor;
                    break;
                }
            }

            if ($anchor === null) {
                continue;
            }

            $testingHistory = $historiesAsc
                ->filter(function ($history) use ($anchor) {
                    if ($history->status !== 'Testing') {
                        return false;
                    }

                    $at = $history->created_at;
                    if ($at === null) {
                        return false;
                    }

                    return $at->lte($anchor);
                })
                ->sortByDesc('created_at')
                ->first();

            if ($testingHistory !== null && ! isset($links[$testingHistory->id])) {
                $links[$testingHistory->id] = $resultId;
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
    private function decodeResultFile(string $path): ?array
    {
        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $flow
     * @return list<string>
     */
    public function validate(array $flow): array
    {
        $errors = [];
        $slug = (string) ($flow['slug'] ?? '');
        $start = (string) ($flow['start'] ?? '');
        /** @var array<string, array<string, mixed>> $steps */
        $steps = $flow['steps'] ?? [];

        if ($slug === '' || ! preg_match('/^[a-z0-9_]+$/', $slug)) {
            $errors[] = 'Slug must be lowercase letters, numbers, and underscores.';
        }

        if ($steps === []) {
            $errors[] = 'Flow must include at least one step.';
        }

        if ($start === '' || ! isset($steps[$start])) {
            $errors[] = 'Start step must exist in the steps map.';
        }

        foreach ($steps as $id => $step) {
            $type = $step['type'] ?? null;
            if (! in_array($type, ['radio', 'none'], true)) {
                $errors[] = "Step {$id} has invalid type.";
                continue;
            }

            if (trim((string) ($step['question'] ?? '')) === '') {
                $errors[] = "Step {$id} needs a question.";
            }

            if ($type === 'none') {
                $status = $step['status'] ?? null;
                if (! is_string($status) || trim($status) === '') {
                    $errors[] = "Terminal step {$id} needs a status.";
                }
                continue;
            }

            $options = $step['options'] ?? [];
            if (! is_array($options) || $options === []) {
                $errors[] = "Radio step {$id} needs at least one option.";
                continue;
            }

            $keys = [];
            foreach ($options as $index => $option) {
                $key = (string) ($option['key'] ?? '');
                if ($key === '') {
                    $errors[] = "Step {$id} option #".($index + 1).' needs a key.';
                    continue;
                }
                if (isset($keys[$key])) {
                    $errors[] = "Step {$id} has duplicate option key {$key}.";
                }
                $keys[$key] = true;

                $next = $option['next'] ?? null;
                $status = $option['status'] ?? null;

                if ($next === null || $next === '') {
                    if (! is_string($status) || trim($status) === '') {
                        $errors[] = "Step {$id} option {$key} must set a next step or a terminal status.";
                    }
                } elseif (! isset($steps[(string) $next])) {
                    $errors[] = "Step {$id} option {$key} points to missing step {$next}.";
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $flow
     * @return array<string, mixed>
     */
    public function normalizeFlow(array $flow, ?string $slug = null): array
    {
        $slug = $this->normalizeSlug((string) ($slug ?? $flow['slug'] ?? ''));
        $stepsIn = is_array($flow['steps'] ?? null) ? $flow['steps'] : [];
        $steps = [];

        foreach ($stepsIn as $id => $step) {
            if (! is_array($step)) {
                continue;
            }

            $stepId = (string) ($step['id'] ?? $id);
            $type = ($step['type'] ?? 'radio') === 'none' ? 'none' : 'radio';
            $normalized = [
                'id' => $stepId,
                'question' => (string) ($step['question'] ?? ''),
                'type' => $type,
                'note' => (bool) ($step['note'] ?? false),
            ];

            if ($type === 'none') {
                $normalized['next'] = null;
                $normalized['status'] = isset($step['status']) && $step['status'] !== ''
                    ? (string) $step['status']
                    : null;
                $normalized['options'] = [];
            } else {
                $options = [];
                foreach (($step['options'] ?? []) as $option) {
                    if (! is_array($option)) {
                        continue;
                    }
                    $next = $option['next'] ?? null;
                    $options[] = [
                        'key' => (string) ($option['key'] ?? ''),
                        'text' => (string) ($option['text'] ?? ''),
                        'next' => ($next === null || $next === '') ? null : (string) $next,
                        'status' => isset($option['status']) && $option['status'] !== ''
                            ? (string) $option['status']
                            : null,
                    ];
                }
                $normalized['options'] = $options;
            }

            $steps[$stepId] = $normalized;
        }

        return [
            'slug' => $slug,
            'name' => (string) ($flow['name'] ?? Str::headline(str_replace('_', ' ', $slug))),
            'version' => (int) ($flow['version'] ?? 1),
            'updated_at' => $flow['updated_at'] ?? null,
            'start' => (string) ($flow['start'] ?? (array_key_first($steps) ?? '')),
            'steps' => $steps,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function modelToArray(TestingFlow $flow): array
    {
        return $this->normalizeFlow([
            'slug' => $flow->slug,
            'name' => $flow->name,
            'version' => $flow->version,
            'updated_at' => optional($flow->updated_at)?->utc()->toIso8601String(),
            'start' => $flow->start,
            'steps' => $flow->steps ?? [],
        ], $flow->slug);
    }

    private function normalizeSlug(string $slug): string
    {
        return Str::lower(trim($slug));
    }
}
