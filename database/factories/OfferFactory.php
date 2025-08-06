<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $offerable_type = $this->faker->randomElement(['App\Models\GroupTrip', 'App\Models\Event']);
        $date = Carbon::now()->subDays($this->faker->numberBetween(1, 10));
        return [
            'offerable_type' => $offerable_type,
            'offerable_id' => rand(1,10),
            'discount' => $this->faker->randomFloat(2,1, 90),
            'start_date' => $date,
            'end_date' => Carbon::parse($date)->addDays($this->faker->numberBetween(1, 30)),
        ];
    }
}
