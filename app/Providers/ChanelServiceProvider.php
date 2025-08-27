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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class ChanelServiceProvider extends ServiceProvider
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
        Broadcast::routes([
            'middleware' => ['auth:api-user,api-guide'],
        ]);

        require base_path('routes/channels.php');
    }
}
