<?php

namespace App\Testing;

class TestingFlowCategoryMapper
{
    /**
     * Map category display names / aliases → flow slug.
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'refrigerator' => 'refrigerators',
        'refrigerators' => 'refrigerators',
        'fridge' => 'refrigerators',
        'fridges' => 'refrigerators',
        'washer' => 'washers',
        'washers' => 'washers',
        'washing machine' => 'washers',
        'washing machines' => 'washers',
        'dryer' => 'dryers',
        'dryers' => 'dryers',
        'range' => 'ranges',
        'ranges' => 'ranges',
        'stove' => 'ranges',
        'stoves' => 'ranges',
        'oven' => 'ranges',
        'ovens' => 'ranges',
        'microwave' => 'microwave',
        'microwaves' => 'microwave',
        'dishwasher' => 'dishwashers',
        'dishwashers' => 'dishwashers',
        'range hood' => 'range_hoods',
        'range hoods' => 'range_hoods',
        'range_hood' => 'range_hoods',
        'range_hoods' => 'range_hoods',
        'hood' => 'range_hoods',
        'hoods' => 'range_hoods',
        'air conditioner' => 'air_conditioners',
        'air conditioners' => 'air_conditioners',
        'air_conditioner' => 'air_conditioners',
        'air_conditioners' => 'air_conditioners',
        'ac' => 'air_conditioners',
        'a/c' => 'air_conditioners',
        'heater' => 'heaters',
        'heaters' => 'heaters',
    ];

    public static function slugFromCategoryName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''));

        if ($normalized === '') {
            return null;
        }

        if (isset(self::ALIASES[$normalized])) {
            return self::ALIASES[$normalized];
        }

        $underscored = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $name) ?? '', '_'));

        return self::ALIASES[$underscored] ?? $underscored ?: null;
    }
}
