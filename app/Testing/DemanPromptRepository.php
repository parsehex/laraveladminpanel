<?php

namespace App\Testing;

use App\Models\DemanFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DemanPromptRepository
{
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

    /**
     * @return list<array{slug: string, name: string, prompt_count: int, updated_at: ?string}>
     */
    public function list(): array
    {
        return DemanFlow::query()
            ->withCount('prompts')
            ->orderBy('slug')
            ->get()
            ->map(fn (DemanFlow $flow) => [
                'slug' => $flow->slug,
                'name' => $flow->name,
                'prompt_count' => (int) $flow->prompts_count,
                'updated_at' => optional($flow->updated_at)?->utc()->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array{slug: string, name: string, prompts: list<array{key: string, description: string}>, updated_at: ?string}|null
     */
    public function get(string $slug): ?array
    {
        $slug = $this->normalizeSlug($slug);
        $flow = DemanFlow::query()->with('prompts')->where('slug', $slug)->first();
        if ($flow === null) {
            return null;
        }

        return [
            'slug' => $flow->slug,
            'name' => $flow->name,
            'updated_at' => optional($flow->updated_at)?->utc()->toIso8601String(),
            'prompts' => $flow->prompts->map(fn ($prompt) => [
                'key' => $prompt->key,
                'description' => $prompt->description,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, string> key => description
     */
    public function promptsForCategory(?string $categoryName): array
    {
        $slug = TestingFlowCategoryMapper::slugFromCategoryName($categoryName);
        if ($slug === null) {
            return [];
        }

        $flow = DemanFlow::query()->with('prompts')->where('slug', $slug)->first();
        if ($flow === null) {
            return [];
        }

        $prompts = [];
        foreach ($flow->prompts as $prompt) {
            $prompts[$prompt->key] = $prompt->description;
        }

        return $prompts;
    }

    /**
     * @param  array{name?: string, prompts?: list<array{key?: string, description?: string}>}  $payload
     * @return array{slug: string, name: string, prompts: list<array{key: string, description: string}>, updated_at: ?string}
     */
    public function save(string $slug, array $payload): array
    {
        $slug = $this->normalizeSlug($slug);
        if ($slug === '' || ! preg_match('/^[a-z0-9_]+$/', $slug)) {
            throw new InvalidArgumentException('Slug must be lowercase letters, numbers, and underscores.');
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Name is required.');
        }

        $rawPrompts = is_array($payload['prompts'] ?? null) ? $payload['prompts'] : [];
        $prompts = [];
        $seen = [];

        foreach ($rawPrompts as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = Str::lower(trim((string) ($row['key'] ?? '')));
            $description = trim((string) ($row['description'] ?? ''));

            if ($key === '' && $description === '') {
                continue;
            }

            if ($key === '' || ! preg_match('/^[a-z0-9_]+$/', $key)) {
                throw new InvalidArgumentException('Each prompt needs a key of lowercase letters, numbers, and underscores.');
            }

            if ($description === '') {
                throw new InvalidArgumentException("Prompt {$key} needs a description.");
            }

            if (isset($seen[$key])) {
                throw new InvalidArgumentException("Duplicate prompt key: {$key}.");
            }

            $seen[$key] = true;
            $prompts[] = [
                'key' => $key,
                'description' => $description,
                'sort_order' => $index,
            ];
        }

        if ($prompts === []) {
            throw new InvalidArgumentException('Add at least one prompt.');
        }

        DB::transaction(function () use ($slug, $name, $prompts) {
            $flow = DemanFlow::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );

            $flow->prompts()->delete();

            foreach ($prompts as $prompt) {
                $flow->prompts()->create($prompt);
            }

            $flow->touch();
        });

        $saved = $this->get($slug);
        if ($saved === null) {
            throw new InvalidArgumentException('Failed to save deman flow.');
        }

        return $saved;
    }

    private function normalizeSlug(string $slug): string
    {
        return Str::lower(trim($slug));
    }
}
