<?php
namespace App\Services;

use App\Http\Resources\EventResource;
use App\Http\Resources\GuideResource;
use App\Models\City;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\LimitedEvents;
use App\Models\Scopes\ActiveScope;
use App\Models\User;
use App\Notifications\PersonalNotification;
use App\Notifications\PublicNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;


class EventService{
    public const FILE_PATH =  'uploads/events/';

    public function index(){
        return EventResource::collection(Event::eventWithRate()
                                                ->activeEvents()
                                                ->withoutOffer()
                                                ->latest()
                                                ->paginate(10));
    }
//
    public function show(Event $event)
    {
        $event->load([
            'category',
            'city',
            'feedbacks' => function ($feedbackQuery) {
                $feedbackQuery->whereHas('user', function ($userQuery) {
                    $userQuery->active();
                });
            }
        ]);

        return new EventResource($event);
    }


    public function store(array $data){
       $event = Event::create($data);
       if($event) {
           $users = User::whereNotNull('fcm_token')->get();
          // $users = User::all();
           foreach ($users as $user) {
               $user->notify(new PublicNotification(
                   'New Event Is Here!',
                   'Try out our new event ' . $event->name,
                   ['id' => $event->id, 'type' => 'event'],
                   $user->fcm_token
               ));
           }
       }
        return $event->refresh();
    }

    public function update(array $data, Event $event){
//        $event->update($data);
//        return new EventResource($event);
         if($data['name']){
             $event->slug = Str::slug($data['name']);
         }
        $name_ar = $data['name_ar'] ?? null;
        $description_ar = $data['description_ar'] ?? null;
        $old_media = empty($data['old_media'] ?? []) ? null : $data['old_media'];


        unset($data['name_ar'], $data['description_ar'],$data['old_media'],$data['media']);


        $event->update($data);

        if ($name_ar) {
            $event->translations()->updateOrCreate(
                ['key' => 'event.name'],
                ['translation' => $name_ar]
            );
        }

        if ($description_ar) {
            $event->translations()->updateOrCreate(
                ['key' => 'event.description'],
                ['translation' => $description_ar]
            );
        }
        $event->updateMedia(self::FILE_PATH,$old_media);
        $event->storeMedia(self::FILE_PATH);
        //->fresh(['translations']
        return new EventResource($event);
    }

    public function destroy(Event $event){
        $event->deleteMedia(self::FILE_PATH);
       return $event->delete();
    }

    public function relatedEvents(Event $event){

            return EventResource::collection(Event::activeEvents()
                ->where('city_id', $event->city_id)
                ->where('id', '!=', $event->id)
                ->with('category')
                ->eventWithRate()
                ->get());
    }

    public function relatedGuides(Event $event){

            return GuideResource::collection(Guide::activeGuides()
                ->where('city_id', $event->city_id)
                ->with('languages')
                ->guideWithRate()
                ->get());

    }

    public function topRatedEvents()
    {
        return EventResource::collection(Event::activeEvents()
                                                ->withoutOffer()
                                                ->eventWithRate()
                                                ->orderByDesc('rating')
                                                ->getByType()

        );
    }

    public function eventsWithOffer()
    {
        return EventResource::collection(Event::activeEvents()
                                                ->hasOffer()
                                                ->eventWithRate()
                                                ->limit(10)
                                                ->get()
        );
    }

    public function makeOffer(array $data,Event $event)
    {
        $offer = DB::transaction(function () use ($data, $event) {
            return $event->offers()->create($data);
        });
        if($offer){
            $users = User::whereNotNull('fcm_token')->get();
            Notification::send($users, new PublicNotification('Check Out Our New Offer!', 'we made a'.$offer->discount.'% discount for the event'.$event->name, ['type' => 'eventWithOffer','id' => $offer->id]));

        }
        return $offer;
    }

    public function makeEventLimited(array $info , $eventId):void
    {
        $info['event_id'] = $eventId;
        LimitedEvents::create($info);
    }

    public function latestEvents()
    {
        return EventResource::collection(Event::eventWithRate()
            ->activeEvents()
            ->withoutOffer()
            ->latest()
            ->limit(10)
            ->get());
    }

}
