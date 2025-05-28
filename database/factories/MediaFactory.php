<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\SoloTrip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {   $taskableModels= [
        User::class,
        GroupTrip::class,
        Guide::class,
        SoloTrip::class,
        City::class,
        Event::class,
    ];
        $taskableTypes=$this->faker->randomElement($taskableModels);
        $taskableInstance=$taskableTypes::inRandomOrder()->first() ?? $taskableTypes::factory();
        return [
            'mediable_type' => $taskableTypes,
            'mediable_id' => $taskableInstance->id,
            'path' => url('storage/images/test.jpg'),

        ];
    }
}
