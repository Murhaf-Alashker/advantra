<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Guide;
use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GuideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guides = Guide::factory(5)->create();
        $categories = Category::all();
        $languages = Language::all();
        $num = rand(1,5);
        foreach ($guides as $guide) {
            $guide->categories()->attach($categories->random($num)->pluck('id')->toArray());
            $guide->languages()->attach($languages->random($num)->pluck('id')->toArray());
        }
    }
}
