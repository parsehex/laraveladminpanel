<?php

namespace App\Testing;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class TestingFlowImporter
{
    /**
     * @var array<string, string>
     */
    private const STATUS_MAP = [
        'Repair' => 'Repair',
        'Show Room' => 'Show Room',
        'Demanufacture' => 'Demanufacture',
        'Ready' => 'Ready',
        'Holding' => 'Holding',
        'Scrap' => 'Scrap',
    ];

    /**
     * @var array<string, string>
     */
    public const NAMES = [
        'refrigerators' => 'Refrigerators',
        'washers' => 'Washers',
        'dryers' => 'Dryers',
        'ranges' => 'Ranges',
        'microwave' => 'Microwaves',
        'dishwashers' => 'Dishwashers',
        'range_hoods' => 'Range Hoods',
        'air_conditioners' => 'Air Conditioners',
        'heaters' => 'Heaters',
    ];

    public function __construct(
        private readonly TestingFlowRepository $repository,
    ) {}

    /**
     * Seed/upsert testing flows from resources/testing-flows/*.json into the DB.
     * Runtime never reads these files — seed/import only.
     *
     * @return list<string> imported slugs (with skipped notes)
     */
    public function importFromJsonTemplates(bool $overwrite = false): array
    {
        $dir = resource_path('testing-flows');
        if (! File::isDirectory($dir)) {
            throw new InvalidArgumentException("Templates directory not found: {$dir}");
        }

        $imported = [];

        foreach (File::files($dir) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $slug = strtolower($file->getFilenameWithoutExtension());
            $existing = $this->repository->get($slug);

            if ($existing !== null && ! $overwrite) {
                $imported[] = $slug.' (skipped)';
                continue;
            }

            $decoded = json_decode(File::get($file->getPathname()), true);
            if (! is_array($decoded)) {
                throw new InvalidArgumentException("Invalid JSON in {$file->getFilename()}");
            }

            $normalized = $this->repository->normalizeFlow($decoded, $slug);
            $normalized['version'] = (int) ($normalized['version'] ?? 1);

            $errors = $this->repository->validate($normalized);
            if ($errors !== []) {
                throw new InvalidArgumentException("Invalid flow {$slug}: ".implode(' ', $errors));
            }

            // Seed/replace head without bumping version history.
            $this->repository->save($normalized, bumpVersion: false);
            $imported[] = $slug;
        }

        return $imported;
    }

    /**
     * @return list<string> imported slugs
     */
    public function importFromPhpConfig(string $path, bool $overwrite = false): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("Config file not found: {$path}");
        }

        /** @var mixed $raw */
        $raw = include $path;

        if (! is_array($raw)) {
            throw new InvalidArgumentException('Config file must return an array.');
        }

        $imported = [];

        foreach ($raw as $slug => $flow) {
            if (! is_array($flow)) {
                continue;
            }

            $slug = strtolower((string) $slug);
            $existing = $this->repository->get($slug);

            if ($existing !== null && ! $overwrite) {
                $imported[] = $slug.' (skipped)';
                continue;
            }

            $normalized = $this->convertLegacyFlow($slug, $flow);
            if ($existing !== null && $overwrite) {
                $normalized['version'] = (int) ($existing['version'] ?? 1);
            }

            $errors = $this->repository->validate($normalized);
            if ($errors !== []) {
                throw new InvalidArgumentException("Invalid flow {$slug}: ".implode(' ', $errors));
            }

            $this->repository->save($normalized, bumpVersion: false);
            $imported[] = $slug;
        }

        return $imported;
    }

    /**
     * @param  array<string, mixed>  $flow
     * @return array<string, mixed>
     */
    public function convertLegacyFlow(string $slug, array $flow): array
    {
        $steps = [];

        foreach (($flow['steps'] ?? []) as $id => $step) {
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
                $normalized['status'] = $this->mapStatus($step['status'] ?? null);
                $normalized['options'] = [];
            } else {
                $options = [];
                foreach (($step['options'] ?? []) as $key => $option) {
                    if (! is_array($option)) {
                        continue;
                    }
                    $next = $option['next'] ?? null;
                    $status = $this->mapStatus($option['status'] ?? null);
                    if ($next === 'submit') {
                        $next = null;
                    } elseif ($next !== null) {
                        $next = (string) $next;
                    }

                    $options[] = [
                        'key' => (string) $key,
                        'text' => (string) ($option['text'] ?? $key),
                        'next' => $next,
                        'status' => $status,
                    ];
                }
                $normalized['options'] = $options;
            }

            $steps[$stepId] = $normalized;
        }

        return [
            'slug' => $slug,
            'name' => self::NAMES[$slug] ?? ucwords(str_replace('_', ' ', $slug)),
            'version' => 1,
            'updated_at' => now()->utc()->toIso8601String(),
            'start' => (string) ($flow['start'] ?? array_key_first($steps) ?? '1'),
            'steps' => $steps,
        ];
    }

    private function mapStatus(mixed $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        $status = (string) $status;

        return self::STATUS_MAP[$status] ?? $status;
    }
}
