<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\SoloTrip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

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
        $taskableInstance=$taskableTypes::inRandomOrder()->first() ?? $taskableTypes::factory()->create();;

        $type = strtolower(class_basename($taskableTypes));
       $folder =  match($type){
            'event','guide','user' =>$type . 's/',
            'city' => 'cities/' ,
            'grouptrip' => 'groupTrips/',
            'solotrip' => 'soloTrips/',

        };
       $mediaType = [['type' => 'images' , 'name' => 'test.jpg'],['type' => 'videos' , 'name' => 'test.mp4']];
       $randElement = $mediaType[array_rand($mediaType)];
        $sourcePath = base_path('resources/'.$randElement['type'].'/'.$randElement['name']);
       $filename = 'test.jpg';
       $path = 'uploads/'.$folder.$taskableInstance->id . '/'.$randElement['type'].'/';
       $fullPath = $path . $filename;
        Storage::disk('public')->put($fullPath, file_get_contents($sourcePath));


        return [
            'mediable_type' => $taskableTypes,
            'mediable_id' => $taskableInstance->id,
            'path' => $filename,
            'type' => $randElement['type'],
        ];
    }
}
