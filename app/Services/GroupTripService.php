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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;


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
        $validated['remaining_tickets'] = $validated['tickets_count'];

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
        if($groupTrip) {
            $users = User::whereNotNull('fcm_token')->get();
            // $users = User::all();
            foreach ($users as $user) {
                $user->notify(new PublicNotification(
                    'New Group Trip Is Here!',
                    'Come Join US In ' . $groupTrip->name,
                    ['id' => $groupTrip->id, 'type' => 'groupTrip'],
                    $user->fcm_token
                ));
            }
        }
        return $groupTrip->refresh();

    }

    public function update(array $data, GroupTrip $group)
    {
        $name_ar = $data['name_ar'] ?? null;
        $description_ar = $data['description_ar'] ?? null;
        $old_media = empty($data['old_media'] ?? []) ? null : $data['old_media'];
        $adding_tickets_count = $data['adding_tickets_count'] ?? 0;

        unset($data['name_ar'], $data['description_ar'], $data['old_media'],$data['media'],$data['adding_tickets_count']);

        $data['tickets_count'] = $adding_tickets_count + $group->tickets_count;
        $data['remaining_tickets'] = $adding_tickets_count + $group->remaining_tickets;

        $group->update($data);

        if ($name_ar) {
            $group->translations()->updateOrCreate(
                ['key' => 'city.name'],
                ['translation' => $name_ar]
            );
        }

        if ($description_ar) {
            $group->translations()->updateOrCreate(
                ['key' => 'city.description'],
                ['translation' => $description_ar]
            );
        }
        $group->updateMedia(self::FILE_PATH,$old_media);

        return new GroupTripResource($group->fresh(['translations']));
    }

    public function topRatedGroupTrips()
    {
        return GroupTripResource::collection(GroupTrip::where('status', Status::FINISHED)
                                                        ->groupTripWithRate()
                                                        ->orderByDesc('rating')
                                                        ->limit(10)
                                                        ->get());
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
                                                        ->limit(10)->get()
        );
    }

    public function makeOffer(array $data,GroupTrip $groupTrip)
    {
        $offer = DB::transaction(function () use ($data, $groupTrip) {
            return  $groupTrip->offers()->create($data);
        });
        if($offer) {
            $users = User::whereNotNull('fcm_token')->get();
            Notification::send($users, new PublicNotification('Check Out Our New Offer!', 'we made a'.$offer->discount.'% discount for the group trip'.$groupTrip->name, ['type' => 'groupTripWithOffer','id' => $offer->id]));
        }
        return $offer;
    }

    public function latestGroupTrips()
    {
        return GroupTripResource::collection(GroupTrip::where('status', Status::PENDING)
            ->orWhere('status', Status::COMPLETED)
            ->withoutOffer()
            ->latest()
            ->groupTripWithRate()
            ->limit(10)->get());
    }


}
