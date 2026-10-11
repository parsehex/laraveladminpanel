<?php

namespace Database\Factories;

use App\Enums\TimeActivity;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity' => TimeActivity::Assembly,
            'user_id' => User::factory(),
            'user_name' => fake()->name(),
            'started_at' => now(),
            'ended_at' => null,
        ];
    }
}
