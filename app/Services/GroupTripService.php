<?php

namespace App\Services;

use App\Enums\Status;
use App\Http\Resources\EventResource;
use App\Http\Resources\GroupTripResource;
use App\Libraries\FileManager;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Offer;
use App\Models\Scopes\ActiveScope;
use App\Models\User;
use App\Notifications\PublicNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class GroupTripService
{
    public const FILE_PATH =  'uploads/groupTrips/';

    public function index()
    {
        return GroupTripResource::collection(GroupTrip::where('status', Status::PENDING)
                                                        ->orWhere('status', Status::COMPLETED)
                                                        ->withoutOffer()
                                                        ->latest()
                                                        ->groupTripWithRate()
                                                        ->paginate(10)

        );
    }

    public function show(GroupTrip $groupTrip)
    {
        $groupTrip->load([
            'feedbacks' => function ($feedbackQuery) {
                $feedbackQuery->whereHas('user', function ($userQuery) {
                    $userQuery->active();
                });
            },
            'events',
            'guide',
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
        $users = User::whereNotNull('fcm_token')->get();
        // $users = User::all();
        foreach ($users as $user) {
            $user->notify(new PublicNotification(
                'New Group Trip Is Here!',
                'Come Join US In ' . $groupTrip->name,
                ['id' => $groupTrip->id, 'type' => 'groupTrip'],
                $user->fcm_token
            ));}
        return $groupTrip->refresh();

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

    public function groupTripsWithOffer()
    {
        return GroupTripResource::collection(GroupTrip::where('status', '=', Status::PENDING)
                                                        ->orWhere('status', '=', Status::COMPLETED)
                                                        ->hasOffer()
                                                        ->paginate(10)
        );
    }

    public function makeOffer(array $data,GroupTrip $groupTrip)
    {
        return DB::transaction(function () use ($data, $groupTrip) {
            return $groupTrip->offers()->create($data);
        });


    }

}
