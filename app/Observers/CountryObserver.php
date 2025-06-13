<?php

namespace App\Observers;

use App\Models\Country;

class CountryObserver
{
    /**
     * Handle the Country "created" event.
     */
    public function created(Country $country): void
    {
        //
    }

    /**
     * Handle the Country "updated" event.
     */
    public function updated(Country $country): void
    {
        //
    }

    public function updating(Country $country){

        if($country->isDirty('status')){
            $country->cities()->update(['status' => $country->status]);
           foreach ($country->cities as $city){
               $city->events()->update(['status' => $city->status]);
               $city->guides()->update(['status' => $city->status]);
           }
            }
        }

    /**
     * Handle the Country "deleted" event.
     */
    public function deleted(Country $country): void
    {
        //
    }

    /**
     * Handle the Country "restored" event.
     */
    public function restored(Country $country): void
    {
        //
    }

    /**
     * Handle the Country "force deleted" event.
     */
    public function forceDeleted(Country $country): void
    {
        //
    }
}
