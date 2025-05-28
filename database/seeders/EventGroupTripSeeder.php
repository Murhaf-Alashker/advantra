<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\GroupTrip;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventGroupTripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groupTrips=GroupTrip::all();
        $events=Event::all();
        foreach ($groupTrips as $groupTrip) {
            $randomEvents=$events->random(rand(2,4))->pluck('id')->toArray();
            $groupTrip->events()->attach($randomEvents);

        }
    }
}
