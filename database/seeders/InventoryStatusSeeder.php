<?php

namespace Database\Seeders;

use App\Models\InventoryStatus;
use App\Support\InventoryStatusAppearance;
use Illuminate\Database\Seeder;

class InventoryStatusSeeder extends Seeder
{
    /**
     * @var array<string, array{auto_location: ?string, color: string}>
     */
    private const STATUSES = [
        'Triage' => ['auto_location' => null, 'color' => '#ffffff'],
        'Testing' => ['auto_location' => null, 'color' => '#67d4f1'],
        'Repair' => ['auto_location' => null, 'color' => '#f97316'],
        'Breakdown' => ['auto_location' => null, 'color' => '#dc2626'],
        'Demanufacture' => ['auto_location' => null, 'color' => '#dc2626'],
        'Cleaning' => ['auto_location' => null, 'color' => '#7c2d12'],
        'Ready' => ['auto_location' => null, 'color' => '#3b82f6'],
        'Scrap' => ['auto_location' => 'Scrap', 'color' => '#111827'],
        'Show Room' => ['auto_location' => 'Showroom', 'color' => '#9333ea'],
        'Sent To Ebay' => ['auto_location' => 'Shopify Sales Ebay Department', 'color' => '#6366f1'],
        'Video' => ['auto_location' => 'Studio', 'color' => '#c026d3'],
        'Quality Control QC' => ['auto_location' => null, 'color' => '#16a34a'],
        'Sold' => ['auto_location' => 'Sold', 'color' => '#059669'],
        'Holding for parts' => ['auto_location' => null, 'color' => '#eab308'],
        'Holding' => ['auto_location' => null, 'color' => '#ec4899'],
    ];

    public function run(): void
    {
        $index = 0;

        foreach (self::STATUSES as $name => $status) {
            $index++;

            $model = InventoryStatus::query()->firstOrCreate(
                ['name' => $name],
                [
                    'auto_location' => $status['auto_location'],
                    'color' => $status['color'],
                    'is_system' => true,
                    'sort_order' => $index,
                    'archived_at' => null,
                ]
            );

            if ($model->wasRecentlyCreated) {
                continue;
            }

            $updates = [];

            if (blank($model->color)) {
                $updates['color'] = $status['color'];
            }

            if ($model->is_system !== true) {
                $updates['is_system'] = true;
            }

            if ($updates !== []) {
                $model->update($updates);
            }
        }

        InventoryStatusAppearance::flush();
    }
}
