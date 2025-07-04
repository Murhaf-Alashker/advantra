<?php

namespace Database\Seeders;

use App\Models\Feedback;
use Database\Factories\FeedbackFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Feedback::factory(10)->create();
    }
}
