<?php

namespace Database\Factories;

use App\Models\GroupTrip;
use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportsLog>
 */
class ReportsLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_path' => $this->faker->filePath(),
            'guide_id' => Guide::inRandomOrder()->first()->id ?? Guide::factory(),
            'group_trip_id' => GroupTrip::inRandomOrder()->first()->id ?? GroupTrip::factory()
        ];
    }
}
