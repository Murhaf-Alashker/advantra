<?php

namespace App\Services;



use App\Http\Resources\GuideResource;
use App\Models\GroupTrip;
use App\Models\Guide;

class GuideService
{
    public const FILE_PATH =  'uploads/guides/';

    public function index()
    {
        return GuideResource::collection(Guide::with(['media'])
                                                ->activeGuides()
                                                ->guideWithRate()
                                                ->paginate(10));
    }

    public function show(Guide $guide)
    {
        $guide->guideWithRate()
            ->load(['media',
            'city',
            'languages',
            'categories',
            'feedbacks' => fn ($query) =>
            $query->whereHas('user', fn ($userQuery) =>
            $userQuery->where('status', 'active'))]);
        return new GuideResource($guide);
    }

    public function topRatedGuides()
    {
        return GuideResource::collection(Guide::with(['media'])
            ->activeGuides()
            ->guideWithRate()
            ->orderByDesc('rating')
            ->paginate(10));
    }
}
