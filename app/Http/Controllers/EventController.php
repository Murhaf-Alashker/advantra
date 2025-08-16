<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferRequest;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Libraries\FileManager;
use App\Models\City;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\LimitedEvents;
use App\Models\Offer;
use App\Services\EventService;
use App\Services\MediaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    protected EventService $eventService;
    public function __construct(EventService $eventService){
        $this->eventService = $eventService;
    }

    public function index(){
        return $this->eventService->index();
    }

    public function show(Event $event){
        return $this->eventService->show($event);
    }

    public function store(StoreEventRequest $request){
       // dd('store method reached');
        $validated = $request->validated();
        $isLimited = $validated['is_limited'];


        $validated['slug']=Str::slug($validated['name']);
        $eventData = collect($validated)->except('is_limited','media','name_ar','description_ar')->all();
        if($isLimited){
            $limited = collect($eventData)->only('tickets_count','tickets_limit','start_date','end_date')->all();
            $limited['remaining_tickets'] = $limited['tickets_count'];
            $eventData = collect($eventData)->except('tickets_count','tickets_limit','start_date','end_date')->all();
        }
        $event =  $this->eventService->store($eventData);



        $event->storeMedia(EventService::FILE_PATH);

        $event->translations()->createMany([
            ['key' => 'event.name',
                'translation' => $validated['name_ar'],
            ],
            [
                'key' => 'event.description',
                'translation' => $validated['description_ar'],
            ]
        ]);
        if($isLimited){
            $this->eventService->makeEventLimited($limited,$event->id);
        }
      //  $event->load('city');
      return response()->json(new EventResource($event),201) ;
    }

    public function update( UpdateEventRequest $request,Event $event){
        $validated = $request->validated();
        return $this->eventService->update($validated,$event);
    }

    public function destroy(Event $event){
        $this->eventService->destroy($event);
        return response()->json([
            'message'=>__('message.deleted_successfully',['attribute' => __('message.attributes.event')]),204
        ]);
    }

    public function relatedEvents(Event $event){
        return $this->eventService->relatedEvents($event);
    }

    public function relatedGuides(Event $event){
       return $this->eventService->relatedGuides($event);
    }

    public function makeOffer(OfferRequest $request,Event $event)
    {
        if($event->hasAnyOffer()){
            return response()->json(['message' => __('message.has_already_offer',['attribute' => __('message.attributes.event')])],400);
        }
        $offer = $this->eventService->makeOffer($request->validated(),$event);
        if(!$offer){
            return response()->json(['message' => __('message.something_wrong')], 400);
        }
        return response()->json(['message' => __('message.created_successfully',['attribute' => __('message.attributes.offer')])],201);
    }


}
