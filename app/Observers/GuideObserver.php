<?php

namespace App\Observers;

use App\Libraries\FileManager;
use App\Models\Guide;
use App\Services\GuideService;

class GuideObserver
{
    /**
     * Handle the Guide "created" event.
     */
    public function created(Guide $guide): void
    {
        //
    }

    /**
     * Handle the Guide "updated" event.
     */
    public function updated(Guide $guide): void
    {
        //
    }

    /**
     * Handle the Guide "deleted" event.
     */
    public function deleted(Guide $guide): void
    {
        $guide->media()->delete();
        FileManager::delete(GuideService::FILE_PATH.$guide->id);
    }

    /**
     * Handle the Guide "restored" event.
     */
    public function restored(Guide $guide): void
    {
        //
    }

    /**
     * Handle the Guide "force deleted" event.
     */
    public function forceDeleted(Guide $guide): void
    {
        //
    }
}
