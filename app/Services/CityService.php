<?php
namespace App\Services;

use App\Http\Resources\CityResource;
use App\Http\Resources\GuideResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Event;
use App\Http\Resources\EventResource;
use App\Models\Scopes\ActiveScope;

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


       unset($data['name_ar'], $data['description_ar']);


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

       return new CityResource($city->fresh(['translations']));
   }


   public function getEvents(City $city)
   {
               return EventResource::collection($city->events()
                    ->where('status', '=', 'active')
                    ->with('category')
                    ->get());

   }

   public function getGuides(City $city)
   {
           return GuideResource::collection($city->guides()
               ->where('status', '=', 'active')
               ->with('languages')
               ->get());
   }

   public function citiesWithMostEvents(){
        $cities = City::withCount('events')
                        ->orderBy('events_count','desc')
                        ->limit(10);
        return CityResource::collection($cities);
   }
}
