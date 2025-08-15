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
use Illuminate\Support\Facades\DB;
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
       return $event;
    }

    public function update(array $data, Event $event){
//        $event->update($data);
//        return new EventResource($event);
         if($data['name']){
             $event->slug = Str::slug($data['name']);
         }
        $name_ar = $data['name_ar'] ?? null;
        $description_ar = $data['description_ar'] ?? null;


        unset($data['name_ar'], $data['description_ar']);


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
        //->fresh(['translations']
        return new EventResource($event);
    }

    public function destroy(Event $event){
       return $event->delete();
    }

    public function relatedEvents(Event $event){
        return EventResource::collection(Event::activeEvents()
                                              ->where('city_id',$event->city_id)
                                              ->where('id','!=',$event->id)
                                              ->eventWithRate()
                                              ->paginate(10));
    }

    public function relatedGuides(Event $event){
        return GuideResource::collection(Guide::activeGuides()
                                               ->where('city_id',$event->city_id)
                                               ->guideWithRate()
                                               ->paginate(5));

    }

    public function topRatedEvents()
    {
        return EventResource::collection(Event::activeEvents()
                                                ->withoutOffer()
                                                ->eventWithRate()
                                                ->orderByDesc('rating')
                                                ->paginate(10));
    }

    public function eventsWithOffer()
    {
        return EventResource::collection(Event::activeEvents()
                                                ->hasOffer()
                                                ->eventWithRate()
                                                ->paginate(10)
        );
    }

    public function makeOffer(array $data,Event $event)
    {
        return DB::transaction(function () use ($data, $event) {
            return $event->offers()->create($data);
        });
    }

    public function makeEventLimited(array $info , $eventId):void
    {
        $info['event_id'] = $eventId;
        LimitedEvents::create($info);
    }

}
