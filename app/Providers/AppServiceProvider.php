<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\City;
use App\Models\Country;
use App\Models\Feedback;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\User;
use App\Observers\CityObserver;
use App\Observers\CountryObserver;
use App\Observers\FeedbackObserver;
use App\Observers\GroupTripObserver;
use App\Observers\GuideObserver;
use App\Observers\UserObserver;
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
        GroupTrip::observe(GroupTripObserver::class);
        Feedback::observe(FeedbackObserver::class);
      //  User::observe(UserObserver::class);

    }
}
