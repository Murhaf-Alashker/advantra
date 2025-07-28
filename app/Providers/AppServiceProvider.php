<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\City;
use App\Models\Country;
use App\Models\Guide;
use App\Observers\CityObserver;
use App\Observers\CountryObserver;
use App\Observers\GuideObserver;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Country::observe(CountryObserver::class);
        City::observe(CityObserver::class);
        Guide::observe(GuideObserver::class);
        Admin::firstOrCreate(
            ['email' => config('admin.default_email')],
            ['password' => Hash::make(config('admin.default_password')),
            'card_number' => config('admin.default_card'),
        ]);
    }
}
