<?php

namespace App\Observers;

use App\Models\Feedback;

class FeedbackObserver
{
    /**
     * Handle the Feedback "created" event.
     */
    public function saved(Feedback $feedback)
    {
        $this->updateFeedbackableRate($feedback);
    }


    /**
     * Handle the Feedback "deleted" event.
     */
    public function deleted(Feedback $feedback): void
    {
        $this->updateFeedbackableRate($feedback);
    }


    private function updateFeedbackableRate(Feedback $feedback): void
    {
        $model = $feedback->feedbackable;
        if($model){
            $model->stars_count = $model->feedbacks()->sum('rating');
            $model->reviewer_count = $model->feedbacks()->count();
            $model->save();
        }
    }
    /**
     * Handle the Feedback "restored" event.
     */
    public function restored(Feedback $feedback): void
    {
        //
    }

    /**
     * Handle the Feedback "force deleted" event.
     */
    public function forceDeleted(Feedback $feedback): void
    {
        //
    }


}
