<?php

namespace Database\Seeders;

use App\Models\SoloTrip;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SoloTripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SoloTrip::factory(5)->create();
    }
}
