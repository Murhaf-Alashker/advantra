<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Resources\CityResource;
use App\Libraries\FileManager;
use App\Models\City;
use App\Models\Country;
use App\Services\CityService;
use App\Services\MediaService;


class CityController extends Controller
{
    protected CityService $cityService;
    public function __construct(CityService $cityService){
        $this->cityService = $cityService;
    }

    public function index()
    {
        return $this->cityService->index();
    }

    public function show(City $city)
    {
        return $this->cityService->show($city);
    }

    public function store(StoreCityRequest $request)
    {
       $validated = $request->validated();
        $cityData = collect($validated)->except('media','name_ar','description_ar')->all();
        $city =   $this->cityService->store($cityData);
        $city->storeMedia(CityService::FILE_PATH);
        $city->translations()->createMany([
           ['key' => 'city.name',
            'translation' => $validated['name_ar'],
           ],
            [
                'key' => 'city.description',
                'translation' => $validated['description_ar'],
            ]
        ]);
        return response()->json(new CityResource($city),201);
    }

    public function update(UpdateCityRequest $request,City $city)
    {
        $validated = $request->validated();
        return $this->cityService->update($validated,$city);
    }

    public function getEvents(City $city)
    {
       return $this->cityService->getEvents($city);
    }

    public function getGuides(City $city)
    {
        return $this->cityService->getGuides($city);
    }

    public function citiesWithMostEvents()
    {
       return $this->cityService->citiesWithMostEvents();
    }
}

