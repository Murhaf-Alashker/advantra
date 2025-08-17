<?php

namespace Database\Seeders;

use App\Models\Country;
use Database\Factories\CountryFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = \App\Enums\Country::values();
        $info = [];
        foreach ($countries as $country) {
            $info[] = ['name'=>$country , 'status'=>'active'];
        }
        Country::insert($info);
        //Country::factory(5)->create();
    }
}
