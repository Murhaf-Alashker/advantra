<?php

namespace Database\Seeders;

use App\Models\GroupTrip;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupTripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GroupTrip::factory(5)->create();
    }
}
