<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {  $taskableModels= [
        User::class,
        GroupTrip::class
    ];
        $taskableTypes=$this->faker->randomElement($taskableModels);
        $taskableInstance=$taskableTypes::inRandomOrder()->first() ?? $taskableTypes::factory();
        return [
            'taskable_type' => $taskableTypes,
            'taskable_id' => $taskableInstance->id,
            'status' => Status::PENDING,
            'start_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d H:i:s'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 months')->format('Y-m-d H:i:s'),
            'guide_id' => Guide::inRandomOrder()->first()->id ?? Guide::factory()
        ];
    }
}
