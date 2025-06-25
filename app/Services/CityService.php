<?php
namespace App\Services;

use App\Http\Resources\CityResource;
use App\Http\Resources\GuideResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Event;
use App\Http\Resources\EventResource;
class CityService{

    public const FILE_PATH =  'uploads/cities/';
    public function index()
    {
        return  CityResource::collection(City::with(['country','language','media'])
                                             ->activeCities()
                                             ->paginate(10));
    }

    public function show(City $city)
    {
        $city->load(['country','language','media']);
       return new CityResource($city);
    }

   public function store(array $data,Country $country)
   {
      $city= $country->cities()->create($data);
      $city->refresh();
        return $city;
   }

   public function update(array $data, City $city)
   {
//       $city->update($data);
//      return new CityResource($city);

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
                                              ->where('status','=','active')
                                              ->with(['media'])
                                              ->paginate(5));
   }

   public function getGuides(City $city)
   {
      return GuideResource::collection($city->guides()
                                            ->where('status','=','active')
                                            ->with('media','languages')
                                            ->paginate(5)
                                           );
   }

   public function citiesWithMostEvents(){
        $cities = City::withCount('events')
                        ->orderBy('events_count','desc')
                        ->paginate(10);
        return CityResource::collection($cities
                                        ->with(['media'])    );
   }
}
