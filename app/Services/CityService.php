<?php
namespace App\Services;

use App\Http\Resources\CityResource;
use App\Http\Resources\GuideResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Event;
use App\Http\Resources\EventResource;
use App\Models\Scopes\ActiveScope;
use Illuminate\Support\Facades\Auth;

class CityService{

    public const FILE_PATH =  'uploads/cities/';
    public function index()
    {
        return  CityResource::collection(City::activeCities()
                                             ->with(['country','language'])
                                             ->paginate(10));
    }

    public function show(City $city)
    {
        $city->load(['country','language']);
       return new CityResource($city);
    }

   public function store(array $data)
   {
      $city= City::create($data);
        return $city;
   }

   public function update(array $data, City $city)
   {
       $name_ar = $data['name_ar'] ?? null;
       $description_ar = $data['description_ar'] ?? null;
       $old_media = empty($data['old_media'] ?? []) ? null : $data['old_media'];


       unset($data['name_ar'], $data['description_ar'], $data['old_media'],$data['media']);


       $city->update($data);

       if ($name_ar) {
           $city->translations()->updateOrCreate(
               ['key' => 'city.name'],
               ['translation' => $name_ar]
           );
       }

       if ($description_ar) {
           $city->translations()->updateOrCreate(
               ['key' => 'city.description'],
               ['translation' => $description_ar]
           );
       }
       $city->updateMedia(self::FILE_PATH,$old_media);
       $city->storeMedia(self::FILE_PATH);

       return new CityResource($city->fresh(['translations']));
   }


   public function getEvents(City $city)
   {
       $events = Auth::guard('api-user')->check() ? $city->events()->where('status', '=', 'active') : $city->events();
               return EventResource::collection($events
                    ->with('category')
                    ->get());

   }

   public function getGuides(City $city)
   {
       $guides =  Auth::guard('api-user')->check() ? $city->guides()->where('status', '=', 'active') : $city->guides();
           return GuideResource::collection($guides
               ->with('languages')
               ->get());
   }

   public function citiesWithMostEvents(){
        $cities = City::withCount('events')
                        ->orderBy('events_count','desc')
                        ->limit(10)->get();
        return CityResource::collection($cities);
   }
}
