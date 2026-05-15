<?php

namespace Database\Seeders;

use App\Models\Truck;
use App\Models\User;
use Illuminate\Database\Seeder;

class TruckSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()->orderBy('id')->value('id');

        if (! $userId) {
            $this->command?->warn('TruckSeeder skipped: no users in database.');

            return;
        }

        $samples = [
            [
                'name' => 'Route 7 Delivery',
                'units_on_truck' => 24,
                'cost_of_truck' => 185000.00,
                'arrival_date' => now()->addDays(3)->toDateString(),
                'status' => 'active',
                'notes' => 'Regional route; restock washers and dryers.',
            ],
            [
                'name' => 'Warehouse Transfer A',
                'units_on_truck' => 12,
                'cost_of_truck' => 92000.50,
                'arrival_date' => now()->subDays(1)->toDateString(),
                'status' => 'active',
                'notes' => null,
            ],
            [
                'name' => 'Seasonal overflow',
                'units_on_truck' => 6,
                'cost_of_truck' => 45000.00,
                'arrival_date' => now()->addWeek()->toDateString(),
                'status' => 'inactive',
                'notes' => 'Parked until Q4 promotion.',
            ],
        ];

        foreach ($samples as $row) {
            Truck::updateOrCreate(
                ['name' => $row['name']],
                array_merge($row, [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])
            );
        }
    }
}
