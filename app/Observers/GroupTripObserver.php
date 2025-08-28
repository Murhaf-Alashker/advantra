<?php

namespace App\Observers;

use App\Enums\Status;
use App\Models\Chat;
use App\Models\GroupTrip;
use App\Models\Guide;
use Illuminate\Support\Carbon;

class GroupTripObserver
{
    /**
     * Handle the GroupTrip "created" event.
     */
    public function created(GroupTrip $groupTrip): void
    {
        $start = Carbon::parse($groupTrip->starting_date)->startOfDay();
        $end = Carbon::parse($groupTrip->ending_date)->endOfDay();
        $groupTrip->tasks()->create([
            'start_date' => $start,
            'end_date' => $end,
            'guide_id' => $groupTrip->guide_id,
        ]);
        $groupTrip->chat()->create([
            'name' => $groupTrip->name . ' ' . $groupTrip->starting_date,
            'guide_id' => $groupTrip->guide_id
        ]);
    }

    /**
     * Handle the GroupTrip "updated" event.
     */
    public function updated(GroupTrip $groupTrip): void
    {
        if($groupTrip->wasChanged('tickets_count')){

            if($groupTrip->tickets_count == 0)
            {
                $groupTrip->update(['status' => Status::COMPLETED->value]);
            }

            else if($groupTrip->status === Status::COMPLETED->value)
            {
                $groupTrip->update(['status' => Status::PENDING->value]);
            }
        }

        if($groupTrip->wasChanged('guide_id'))
        {
            //رسالة للغايد الجديد
        }

    }

    /**
     * Handle the GroupTrip "deleted" event.
     */
    public function deleted(GroupTrip $groupTrip): void
    {
        //
    }

    /**
     * Handle the GroupTrip "restored" event.
     */
    public function restored(GroupTrip $groupTrip): void
    {
        //
    }

    /**
     * Handle the GroupTrip "force deleted" event.
     */
    public function forceDeleted(GroupTrip $groupTrip): void
    {
        //
    }
}
