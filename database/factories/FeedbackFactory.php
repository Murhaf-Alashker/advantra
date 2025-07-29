<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {  $feedbackableModels= [
        Guide::class,
        GroupTrip::class,
        Event::class
    ];
        $feedbackableTypes=$this->faker->randomElement($feedbackableModels);
        $feedbackableInstance=$feedbackableTypes::inRandomOrder()->first() ?? $feedbackableTypes::factory()->create();
        return [
            'feedbackable_type' => $feedbackableTypes,
            'feedbackable_id' => $feedbackableInstance->id,
            'rating' => $this->faker->randomFloat(1, 1, 5),
            'comment' => $this->faker->realText(),
            'user_id' => User::inRandomOrder()->first() ?? User::factory(),
        ];
    }
}
