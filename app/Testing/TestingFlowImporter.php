<?php

namespace App\Testing;

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
    private const NAMES = [
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
        $dir = $this->repository->templatesPath();

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($raw as $slug => $flow) {
            if (! is_array($flow)) {
                continue;
            }

            $slug = strtolower((string) $slug);
            $templatePath = $dir.'/'.$slug.'.json';

            if (! $overwrite && is_file($templatePath)) {
                $imported[] = $slug.' (skipped)';
                continue;
            }

            $normalized = $this->convertLegacyFlow($slug, $flow);
            $errors = $this->repository->validate($normalized);
            if ($errors !== []) {
                throw new InvalidArgumentException("Invalid flow {$slug}: ".implode(' ', $errors));
            }

            file_put_contents(
                $templatePath,
                json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            );
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
