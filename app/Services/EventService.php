<?php
namespace App\Services;

use App\Http\Resources\EventResource;
use App\Http\Resources\GuideResource;
use App\Models\City;
use App\Models\Event;
use App\Models\Guide;


class EventService{
    public const FILE_PATH =  'uploads/events/';

    public function index(){
        return EventResource::collection(Event::with(['media'])
                                                ->activeEvents()
                                                ->eventWithRate()
                                                ->latest()
                                                ->paginate(10));
    }

    public function show(Event $event){
        $event->load(['city','category','media']);
        return new EventResource($event);
    }

    public function store(array $data,City $city){
       $event=  $city->events()->create($data);
       $event->refresh();
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
        return EventResource::collection(Event::where('city_id',$event->city_id)
                                              ->where('id','!=',$event->id)
                                              ->where('status','active')
                                              ->enentWithRate()
                                              ->paginate(10));
    }

    public function relatedGuides(Event $event){
        return GuideResource::collection(Guide::where('city_id',$event->city_id)
                                               ->where('status','active')
                                               ->guideWithRate()
                                               ->paginate(5));

    }

    public function topRatedEvents()
    {
        return EventResource::collection(Event::with(['media'])
                                                ->activeEvents()
                                                ->eventWithRate()
                                                ->orderByDesc('rating')
                                                ->paginate(10));
    }

}
