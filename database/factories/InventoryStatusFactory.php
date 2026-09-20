<?php

namespace Database\Factories;

use App\Models\InventoryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryStatus>
 */
class InventoryStatusFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Custom '.fake()->unique()->bothify('Status-####'),
            'auto_location' => null,
            'is_system' => false,
            'sort_order' => fake()->numberBetween(100, 999),
            'archived_at' => null,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
