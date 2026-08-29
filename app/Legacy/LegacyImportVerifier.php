<?php

namespace App\Legacy;

use Illuminate\Support\Facades\DB;

class LegacyImportVerifier
{
    public function verify(?LegacyDumpReader $dump = null): LegacyImportReport
    {
        $report = new LegacyImportReport;

        $counts = [
            'trucks' => DB::table('trucks')->count(),
            'truck_appliances' => DB::table('truck_appliances')->count(),
            'models' => DB::table('models')->count(),
            'parts' => DB::table('parts')->count(),
            'model_parts' => DB::table('model_parts')->count(),
            'inventory_status_histories' => DB::table('inventory_status_histories')->count(),
            'user_actions' => DB::table('user_actions')->count(),
            'testing_results' => DB::table('testing_results')->count(),
            'repair_results' => DB::table('repair_results')->count(),
            'legacy_id_map' => DB::table('legacy_id_map')->count(),
        ];

        $report->section('counts', $counts);

        if ($dump !== null) {
            $expectedTesting = $dump->count('testing_results');
            $actualTesting = ($counts['testing_results'] ?? 0) + ($counts['repair_results'] ?? 0);

            if ($actualTesting < $expectedTesting) {
                $report->warn("testing_results+repair_results: expected at least {$expectedTesting}, found {$actualTesting}");
            }

            $expected = [
                'trucks' => $dump->count('trucks'),
                'truck_appliances' => $dump->count('truck_items'),
                'models' => $dump->count('models'),
                'parts' => $dump->count('parts'),
                'model_parts' => $dump->count('model_parts'),
                'inventory_status_histories' => $dump->count('inventory_status_history'),
                'user_actions' => $dump->count('user_actions'),
            ];

            foreach ($expected as $table => $expectedCount) {
                $actualKey = $table;
                $actual = $counts[$actualKey] ?? 0;

                if ($actual < $expectedCount) {
                    $report->warn("{$table}: expected at least {$expectedCount}, found {$actual}");
                }
            }
        }

        $kpiEligible = DB::table('inventory_status_histories')
            ->whereIn('status', ['Repair', 'Testing', 'Cleaning'])
            ->count();

        $kpiJoined = DB::table('inventory_status_histories')
            ->join('users', 'users.id', '=', 'inventory_status_histories.user_id')
            ->whereIn('inventory_status_histories.status', ['Repair', 'Testing', 'Cleaning'])
            ->count();

        $report->section('dashboard_kpi_coverage', [
            'eligible_rows' => $kpiEligible,
            'rows_with_user_join' => $kpiJoined,
            'percent' => $kpiEligible > 0 ? round(($kpiJoined / $kpiEligible) * 100, 1) : 100,
        ]);

        if ($kpiEligible > 0 && $kpiJoined < $kpiEligible) {
            $missing = $kpiEligible - $kpiJoined;
            $report->warn("Dashboard KPIs will omit {$missing} status history rows with null/unjoinable user_id");
        }

        return $report;
    }
}
