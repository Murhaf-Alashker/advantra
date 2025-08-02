<?php
namespace App\Services;

use App\Http\Resources\EventResource;
use App\Http\Resources\GuideResource;
use App\Models\City;
use App\Models\Event;
use App\Models\Guide;
use App\Models\Scopes\ActiveScope;


class EventService{
    public const FILE_PATH =  'uploads/events/';

    public function index(){
        return EventResource::collection(Event::eventWithRate()
                                                ->latest()
                                                ->paginate(10));
    }
//
    public function show(Event $event){
        $event->load([
            'city' => function ($query) {$query->withoutGlobalScopes(ActiveScope::class);},
            'category'
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

        return new EventResource($event->fresh(['translations']));
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
                                                ->eventWithRate()
                                                ->orderByDesc('rating')
                                                ->paginate(10));
    }

}
