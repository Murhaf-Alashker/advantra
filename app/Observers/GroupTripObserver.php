<?php

namespace App\Observers;

use App\Enums\Status;
use App\Models\GroupTrip;
use App\Models\Guide;

class GroupTripObserver
{
    /**
     * Handle the GroupTrip "created" event.
     */
    public function created(GroupTrip $groupTrip): void
    {
        $groupTrip->tasks()->create([
            'start_date' => $groupTrip->starting_date,
            'end_date' => $groupTrip->ending_date,
            'guide_id' => $groupTrip->guide_id,
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
                $groupTrip->update(['status' => Status::COMPLETED]);
            }

            else if($groupTrip->status == Status::COMPLETED)
            {
                $groupTrip->update(['status' => Status::PENDING]);
            }
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
