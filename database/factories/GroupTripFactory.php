<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupTrip>
 */
class GroupTripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'starting_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d H:i:s'),
            'ending_date' => fake()->dateTimeBetween('+1 month', '+2 months')->format('Y-m-d H:i:s'),
            'status'=> Status::PENDING,
            'price'=>$this->faker->randomFloat(2, 50.00, 99999999.99),
            'tickets_count'=>$this->faker->numberBetween(100,1000),
            'tickets_limit' => $this->faker->numberBetween(10,100),
            'stars_count'=>0,
            'reviews_count'=>0,
            'basic_cost'=>rand(50.00,100.00),
            'extra_cost' => rand(1.00,50.00),
            'guide_id' => Guide::inRandomOrder()->first()->id ?? Guide::factory()


        ];
    }
}
