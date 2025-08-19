<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
        $reviewer = rand(1 ,1000);

        return [
            'name'=>$this->faker->name(),
            'email'=>$this->faker->unique()->safeEmail(),
            'password'=>Hash::make('guidePassword'),
            'phone'=>$this->faker->phoneNumber(),
            'description'=>$this->faker->text(),
            'card'=>$this->faker->unique()->numerify('##########'),
            'status'=>'active',
            'price'=>$this->faker->randomFloat(2, 50.00, 999999.99),
            'const_salary'=>100.00,
            'extra_salary'=>0.00,
            'stars_count'=>$reviewer * $this->faker->randomFloat(1,1,5),
            'reviewer_count'=>$reviewer,
            'city_id' => City::inRandomOrder()->first()->id ?? City::factory()


        ];
    }
}
