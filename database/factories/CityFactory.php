<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->city(),
            'description' => $this->faker->realText(),
            'status' => 'active',
            'country_id' => Country::inRandomOrder()->first()->id ?? Country::factory(),
            'language_id' => Language::inRandomOrder()->first()->id ?? Language::factory(),
        ];
    }
}
