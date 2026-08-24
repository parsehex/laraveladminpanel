<?php

namespace App\Testing;

use Illuminate\Support\Str;
use InvalidArgumentException;

class DemanFlowImporter
{
    public function __construct(
        private readonly DemanPromptRepository $repository,
    ) {}

    /**
     * Seed deman flows from config/deman_prompts.php (or a given config array).
     *
     * @param  array<string, array<string, string>>|null  $config
     * @return list<string>
     */
    public function importFromConfig(?array $config = null, bool $overwrite = false): array
    {
        $config ??= config('deman_prompts', []);
        if (! is_array($config)) {
            throw new InvalidArgumentException('deman_prompts config must be an array.');
        }

        $imported = [];

        foreach ($config as $slug => $prompts) {
            if (! is_array($prompts)) {
                continue;
            }

            $slug = Str::lower((string) $slug);
            $existing = $this->repository->get($slug);

            if ($existing !== null && ! $overwrite) {
                $imported[] = $slug.' (skipped)';
                continue;
            }

            $rows = [];
            foreach ($prompts as $key => $description) {
                $rows[] = [
                    'key' => (string) $key,
                    'description' => (string) $description,
                ];
            }

            $this->repository->save($slug, [
                'name' => DemanPromptRepository::NAMES[$slug]
                    ?? Str::headline(str_replace('_', ' ', $slug)),
                'prompts' => $rows,
            ]);

            $imported[] = $slug;
        }

        return $imported;
    }
}
