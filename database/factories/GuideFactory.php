<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guide>
 */
class GuideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'=>$this->faker->name(),
            'email'=>$this->faker->unique()->safeEmail(),
            'phone'=>$this->faker->phoneNumber(),
            'description'=>$this->faker->text(),
            'card'=>$this->faker->unique()->numerify('##########'),
            'status'=>'inactive',
            'price'=>$this->faker->randomFloat(2, 50.00, 999999.99),
            'const_salary'=>100.00,
            'extra_salary'=>0.00,
            'stars_count'=>0,
            'reviews_count'=>0,
            'city_id' => City::inRandomOrder()->first()->id ?? City::factory()


        ];
    }
}
