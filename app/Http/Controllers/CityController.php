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
  //  public $UPLOUD_PAHT = 'cities/';
    protected CityService $cityService;
    protected MediaService $mediaService;
    protected  FileManager $fileManager;
    public function __construct(CityService $cityService,MediaService $mediaService,FileManager $fileManager){
        $this->cityService = $cityService;
        $this->mediaService = $mediaService;
        $this->fileManager = $fileManager;
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
        $cityData = collect($validated)->except('images','name_ar','description_ar')->all();
        $city =   $this->cityService->store($cityData,$country);
        $path = CityService::FILE_PATH . $city->id;
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $filenames = $this->fileManager->storeMany($path, $images, 'pic');
            $mediaData = [];

            foreach ($filenames as $filename) {
                $mediaData[] = [
                    'path' => $filename,
                ];
            }
            $city->media()->createMany($mediaData);
        }

        $city->translations()->createMany([
           ['key' => 'city.name',
            'translation' => $validated['name_ar'],
           ],
            [
                'key' => 'city.description',
                'translation' => $validated['description_ar'],
            ]
        ]);
        $city->load('media');
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

