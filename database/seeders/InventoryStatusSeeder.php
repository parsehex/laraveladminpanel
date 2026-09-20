<?php

namespace Database\Seeders;

use App\Models\InventoryStatus;
use Illuminate\Database\Seeder;

class InventoryStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Triage', 'auto_location' => null],
            ['name' => 'Testing', 'auto_location' => null],
            ['name' => 'Repair', 'auto_location' => null],
            ['name' => 'Breakdown', 'auto_location' => null],
            ['name' => 'Demanufacture', 'auto_location' => null],
            ['name' => 'Cleaning', 'auto_location' => null],
            ['name' => 'Ready', 'auto_location' => null],
            ['name' => 'Scrap', 'auto_location' => 'Scrap'],
            ['name' => 'Show Room', 'auto_location' => 'Showroom'],
            ['name' => 'Sent To Ebay', 'auto_location' => 'Shopify Sales Ebay Department'],
            ['name' => 'Video', 'auto_location' => 'Studio'],
            ['name' => 'Quality Control QC', 'auto_location' => null],
            ['name' => 'Sold', 'auto_location' => 'Sold'],
            ['name' => 'Holding for parts', 'auto_location' => null],
            ['name' => 'Holding', 'auto_location' => null],
        ];

        foreach ($statuses as $index => $status) {
            InventoryStatus::query()->updateOrCreate(
                ['name' => $status['name']],
                [
                    'auto_location' => $status['auto_location'],
                    'is_system' => true,
                    'sort_order' => $index + 1,
                    'archived_at' => null,
                ]
            );
        }
    }
}
