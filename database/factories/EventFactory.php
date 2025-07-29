<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->sentence(2);
        return [
            'name'=>$name,
            'slug'=>Str::slug($name),
            'description'=>$this->faker->realText(),
            'price'=>$this->faker->randomFloat(2,10,500),
            'status'=>'active',
            'stars_count'=>0,
            'reviewer_count'=>0,
            'basic_cost'=>rand(0.00,100.00),
            'city_id' => City::inRandomOrder()->first()->id ?? City::factory(),
            'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory()


        ];
    }
}
