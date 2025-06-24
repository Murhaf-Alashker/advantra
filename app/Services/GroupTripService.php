<?php

namespace App\Services;

use App\Enums\Status;
use App\Http\Resources\GroupTripResource;
use App\Models\GroupTrip;

class GroupTripService
{
    public const FILE_PATH =  'uploads/groupTrips/';

    public function index()
    {
        return GroupTripResource::collection(GroupTrip::with(['media'])
                                                        ->where('status', Status::PENDING)
                                                        ->orWhere('status', Status::COMPLETED)
                                                        ->latest()
                                                        ->groupTripWithRate()
                                                        ->paginate(10)

        );
    }

    public function show(GroupTrip $groupTrip)
    {
        $groupTrip->load(['media', 'feedbacks', 'events' , 'guide', 'cities']);
        return new GroupTripResource($groupTrip);
    }

    public function topRatedGroupTrips()
    {
        return GroupTripResource::collection(GroupTrip::with(['media'])
            ->where('status', Status::FINISHED)
            ->groupTripWithRate()
            ->orderByDesc('rating')
            ->paginate(10));
    }
}
