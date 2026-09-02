<?php

namespace App\Legacy;

use Illuminate\Support\Facades\DB;

class LegacyImportReset
{
    /** @var list<string> */
    private const TRUNCATE_TABLES = [
        'repair_results',
        'testing_results',
        'repair_diagnoses',
        'appliance_parts',
        'deman_parts',
        'inventory_status_histories',
        'user_actions',
        'delivery_truck_appliance',
        'truck_appliances',
        'model_parts',
        'parts',
        'models',
        'subcategories',
        'custom_sales',
        'suggestions',
        'trucks',
        'brands',
    ];

    public function __construct(
        private readonly LegacyIdMapRepository $idMap,
    ) {}

    public function run(bool $dryRun = false): void
    {
        if ($dryRun) {
            return;
        }

        $tables = implode(', ', self::TRUNCATE_TABLES);
        DB::statement("TRUNCATE TABLE {$tables} RESTART IDENTITY CASCADE");

        $this->idMap->forgetAll();

        DB::table('users')
            ->where('email', 'like', '%@legacy-import.local')
            ->delete();
    }

    /**
     * @return list<string>
     */
    public function tables(): array
    {
        return self::TRUNCATE_TABLES;
    }
}
