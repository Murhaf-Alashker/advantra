<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Libraries\FileManager;
use App\Models\City;
use App\Models\Event;
use App\Services\EventService;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public $UPLOUD_PAHT = 'events/';
    protected MediaService $mediaService;
    protected EventService $eventService;
    protected  FileManager $fileManager;
    public function __construct(EventService $eventService,MediaService $mediaService,FileManager $fileManager){
        $this->eventService = $eventService;
        $this->mediaService = $mediaService;
        $this->fileManager = $fileManager;
    }

    public function index(){
        return $this->eventService->index();
    }

    public function show(Event $event){
        return $this->eventService->show($event);
    }

    public function store(StoreEventRequest $request,City $city){
        $validated = $request->validated();
        $validated['slug']=Str::slug($validated['name']);
        $eventData = collect($validated)->except('images','name_ar','description_ar')->all();
        $event =  $this->eventService->store($eventData,$city);
        $path = $this->UPLOUD_PAHT.$event->id;
        if($request->hasFile('images')) {
            $images = $request->file('images');
            $filenames = $this->fileManager->storeMany($path, $images, 'pic');
            $mediaData = [];

            foreach ($filenames as $filename) {
                $mediaData[] = [
                    'path' => $filename,
                ];
            }
            $event->media()->createMany($mediaData);
        }
//            $data = [
//                'mediable_type' => 'event',
//                'mediable_id' => $event->id,
//                'images' => $images,
//            ];
//            $this->mediaService->uploadImages($data);
           /* if(is_array($images))
            {
                $this->mediaService->storeMany($event, $images);
            }else
            {
                $this->mediaService->store($event, $images);
            }*/
      //  }

        $event->translations()->createMany([
            ['key' => 'event.name',
                'translation' => $validated['name_ar'],
            ],
            [
                'key' => 'event.description',
                'translation' => $validated['description_ar'],
            ]
        ]);
       $event->load('media');
       return response()->json(new EventResource($event),201) ;
    }

    public function update( UpdateEventRequest $request,Event $event){
        $validated = $request->validated();
        return $this->eventService->update($validated,$event);
    }

    public function destroy(Event $event){
        $this->eventService->destroy($event);
        return response()->json([
            'message'=>'Event deleted!',204
        ]);
    }

    public function relatedEvents(Event $event){
        return $this->eventService->relatedEvents($event);
    }

    public function relatedGuides(Event $event){
       return $this->eventService->relatedGuides($event);
    }

}
