<?php

namespace App\Services;

use App\Enums\Status;
use App\Http\Resources\GroupTripResource;
use App\Libraries\FileManager;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Scopes\ActiveScope;

class GroupTripService
{
    public const FILE_PATH =  'uploads/groupTrips/';

    public function index()
    {
        return GroupTripResource::collection(GroupTrip::where('status', Status::PENDING)
                                                        ->orWhere('status', Status::COMPLETED)
                                                        ->latest()
                                                        ->groupTripWithRate()
                                                        ->paginate(10)

        );
    }

    public function show(GroupTrip $groupTrip)
    {
        $groupTrip->load([
            'feedbacks',
            'events' => function ($query) {$query->withoutGlobalScope(ActiveScope::class);},
            'guide' => function ($query) {$query->withoutGlobalScope(ActiveScope::class);},
        ]);
        return new GroupTripResource($groupTrip);
    }

    public function store(array $validated)
    {
        $data = collect($validated)->except('media','events','name_ar','description_ar')->all();

        $groupTrip = GroupTrip::create($data);

        $events = Event::whereIn('id', $validated['events'])->pluck('id')->toArray();

        $groupTrip->events()->sync($events);

        $groupTrip->translations()->createMany([
            ['key' => 'group_trip.name',
                'translation' => $validated['name_ar'],
            ],
            [
                'key' => 'group_trip.description',
                'translation' => $validated['description_ar'],
            ]
        ]);

        $groupTrip->storeMedia(self::FILE_PATH);

        return $groupTrip->refresh()->withoutGlobalScope(ActiveScope::class)->where('id',$groupTrip->id)->first();

    }

    public function topRatedGroupTrips()
    {
        return GroupTripResource::collection(GroupTrip::where('status', Status::FINISHED)
            ->groupTripWithRate()
            ->orderByDesc('rating')
            ->paginate(10));
    }

    public function destroy(GroupTrip $groupTrip): void
    {
        $groupTrip->deleteMedia(self::FILE_PATH);
        $groupTrip->delete();
    }

}
