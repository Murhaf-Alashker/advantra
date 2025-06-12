<?php

namespace App\Providers;

use App\Models\City;
use App\Models\Country;
use App\Observers\CityObserver;
use App\Observers\CountryObserver;
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
    }
}
