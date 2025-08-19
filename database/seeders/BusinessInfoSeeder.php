<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\BusinessInfo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusinessInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now()->startOfMonth();
        BusinessInfo::factory()->create();
        for ($i = 0; $i < 12; $i++) {
            $date = $now->copy()->subMonths($i);
            if($i===0) continue;
            DB::table('business_infos')->insert([
                'total_profit' => rand(1000, 10000),
                'total_income' => rand(5000, 20000),
                'events_reserved_tickets' => rand(50, 500),
                'group_trip_reserved_tickets' => rand(20, 200),
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }
}
