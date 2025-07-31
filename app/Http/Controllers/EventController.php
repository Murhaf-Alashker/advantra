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
use App\Models\Offer;
use App\Services\EventService;
use App\Services\MediaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
  //  public const UPLOAD_PAHT = 'events/';

    protected MediaService $mediaService;
    protected EventService $eventService;
    public function __construct(EventService $eventService,MediaService $mediaService){
        $this->eventService = $eventService;
        $this->mediaService = $mediaService;
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
        $eventData = collect($validated)->except('media','name_ar','description_ar')->all();
        $event =  $this->eventService->store($eventData,$city);
        $path = EventService::FILE_PATH . $event->id;

        if($request->hasFile('media')) {
            $allFiles = array_filter($request->file('media'), fn($file) => $file->isValid());
            if(count($allFiles) > 0) {
                $files = FileManager::storeMany($path, $allFiles);
                /*$mediaData = [];

                foreach ($filenames as $filename) {
                    $mediaData[] = [
                        'path' => $filename,
                    ];
                }*/
                $event->media()->createMany($files);
            }
        }

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
            'message'=>__('message.deleted_successfully',['attribute' => __('message.attributes.event')]),204
        ]);
    }

    public function relatedEvents(Event $event){
        return $this->eventService->relatedEvents($event);
    }

    public function relatedGuides(Event $event){
       return $this->eventService->relatedGuides($event);
    }

}
