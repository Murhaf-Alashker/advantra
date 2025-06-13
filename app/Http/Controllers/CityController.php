<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Models\Country;
use App\Services\CityService;
use App\Services\MediaService;


class CityController extends Controller
{
    protected CityService $cityService;
    protected MediaService $mediaService;
    public function __construct(CityService $cityService,MediaService $mediaService){
        $this->cityService = $cityService;
        $this->mediaService = $mediaService;
    }

    public function index()
    {
        return $this->cityService->index();
    }

    public function show(City $city)
    {
        return $this->cityService->show($city);
    }

    public function store(StoreCityRequest $request,Country $country)
    {
       $validated = $request->validated();
        $cityData = collect($validated)->except('images')->all();
        $city =   $this->cityService->store($cityData,$country);
        if ($request->hasFile('images')) {
            $images = $request->file('images');
           if(is_array($images))
           {
               $this->mediaService->storeMany($city, $images);
           }else
           {
               $this->mediaService->store($city, $images);
           }
        }
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

