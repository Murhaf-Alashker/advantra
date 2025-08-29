<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rooms_num = rand(10,20);
        $rooms = [];
        for ($i = 1; $i < $rooms_num; $i++) {
            $rooms[] = $this->fakeInfo();
        }
        return [
            'name' => $this->faker->name(),
            'address' => $this->faker->address(),
            'reservation_number' => $this->faker->phoneNumber(),
            'description' => $this->faker->paragraph(5),
            'rate' => $this->faker->numberBetween(1,5),
            'info' => json_encode($rooms),
        ];
    }

    public function fakeInfo()
    {
        return (object)[
                'persons_count' => (int) rand(1,5),
                'description' => $this->faker->paragraph(3),
                'price' => $this->faker->randomFloat(20,1000),

            ];
    }
}
