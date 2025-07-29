<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\SoloTrip;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SoloTripEventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $soloTrips = SoloTrip::all();
        $events = Event::all();
        foreach ($soloTrips as $soloTrip) {
            $randomEvents = $events->random(rand(2, 4));
            $attachData = [];
            foreach ($randomEvents as $event) {
                $attachData[$event->id] = [
                    'price' => $event->price,
                ];
            }
            $soloTrip->events()->attach($attachData);

        }
    }
}
