<?php

namespace App\Services;



use App\Http\Resources\GuideResource;
use App\Libraries\FileManager;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\WithMediaScope;
use Illuminate\Support\Facades\Auth;

class GuideService
{
    public const FILE_PATH =  'uploads/guides/';

    public function __construct()
    {
    }
    public function index()
    {
        return GuideResource::collection(Guide::activeGuides()
                                        ->guideWithRate()
                                        ->paginate(10));
    }

    public function show(Guide $guide)
    {
        $guide->guideWithRate()
            ->firstOrFail();
        return new GuideResource($guide);
    }

    public function update(Guide $guide, array $data)
    {
        $guide->update($data);
        //->fresh(['languages', 'categories', 'feedbacks']);
        return $guide;

    }

    public function store(array $data)
    {
        $guide = Guide::create($data);

        return $guide;
    }

    public function destroy(Guide $guide): bool
    {
        return $guide->delete();
    }

    public function topRatedGuides()
    {
        return GuideResource::collection(Guide::ActiveGuides()
                                        ->guideWithRate()
                                        ->orderByDesc('rating')
                                        ->limit(10));
    }

    public function relatedGuides(Guide $guide)
    {
        return GuideResource::collection(Guide::ActiveGuides()
                                                ->where('city_id', '=', $guide->city_id)
                                                ->where('id', '!=', $guide->id)
                                                ->guideWithRate()
                                                ->get());
    }

    public function trashedGuides()
    {
        return GuideResource::collection(Guide::onlyTrashed()
                                                ->guideWithRate()
                                                ->paginate(10));
    }
}
