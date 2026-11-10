<?php

namespace Database\Factories;

use App\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLocation>
 */
class InventoryLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Location '.fake()->unique()->bothify('####'),
        ];
    }
}
