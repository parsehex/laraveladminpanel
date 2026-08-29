<?php

namespace App\Legacy;

use App\Models\Category;
use Illuminate\Support\Collection;

class LegacyCategoryResolver
{
    /** @var array<string, string> */
    private array $aliases = [
        'refrigerators' => 'Refrigerators',
        'ranges' => 'Ranges',
        'washers' => 'Washers',
        'dryers' => 'Dryers',
        'microwave' => 'Microwave',
        'dishwasher' => 'Dishwasher',
        'heater' => 'Heater',
        'pedestal' => 'Pedestal',
        'air_conditioner' => 'Air Conditioner',
        'air conditioner' => 'Air Conditioner',
    ];

    /** @var Collection<string, int> */
    private Collection $byName;

    /** @var array<string, string> */
    private array $patchOverrides;

    public function __construct(array $patchOverrides = [])
    {
        $this->patchOverrides = $patchOverrides;
        $this->byName = Category::query()->pluck('id', 'name');
    }

    public function resolve(?string $raw): ?int
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $canonical = $this->patchOverrides[$raw]
            ?? $this->patchOverrides[strtolower($raw)]
            ?? $this->canonicalName($raw);

        return $this->byName->get($canonical);
    }

    public function slugFromCategoryName(?string $raw): ?string
    {
        $canonical = $this->canonicalName($raw);
        if ($canonical === null) {
            return null;
        }

        return match ($canonical) {
            'Refrigerators' => 'refrigerators',
            'Ranges' => 'ranges',
            'Washers' => 'washers',
            'Dryers' => 'dryers',
            'Microwave' => 'microwave',
            'Dishwasher' => 'dishwashers',
            'Heater' => 'heaters',
            'Air Conditioner' => 'air_conditioners',
            'Pedestal' => null,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public function unknownValues(LegacyDumpReader $dump, string $table, string $column): array
    {
        $unknown = [];

        foreach ($dump->rows($table) as $row) {
            $raw = $row[$column] ?? null;
            if ($raw === null || trim((string) $raw) === '') {
                continue;
            }

            if ($this->resolve((string) $raw) === null) {
                $unknown[(string) $raw] = true;
            }
        }

        return array_keys($unknown);
    }

    private function canonicalName(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $trimmed = trim($raw);
        $lower = strtolower(str_replace('_', ' ', $trimmed));

        if (isset($this->aliases[$lower])) {
            return $this->aliases[$lower];
        }

        if (isset($this->aliases[str_replace(' ', '_', $lower)])) {
            return $this->aliases[str_replace(' ', '_', $lower)];
        }

        foreach ($this->byName->keys() as $name) {
            if (strcasecmp($name, $trimmed) === 0) {
                return $name;
            }
        }

        return $trimmed;
    }
}
