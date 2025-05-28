<?php

namespace Database\Seeders;

use App\Models\ReportsLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportsLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReportsLog::factory(5)->create();
    }
}
