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
    protected string $type = "";
    protected int $count = 5;

    public function __construct()
    {
        if(Auth::guard('api-user')->check()){
            $this->type = "user";
        }
        elseif (Auth::guard('api-admin')->check()){
            $this->type = "admin";
            $this->count = 10;
        }
    }
    public function index()
    {
        return GuideResource::collection(Guide::guideWithRate()
                                        ->paginate($this->count));
    }

    public function show($guideID)
    {
        $guide=Guide::withoutGlobalScope(ActiveScope::class)
            ->where('id', '=', $guideID)
            ->guideWithRate()
            ->with([
                'city' => function ($query) {$query->withoutGlobalScope(ActiveScope::class);},
                'languages',
                'categories',
                'feedbacks' => fn ($query) =>
                $query->whereHas('user')])
            ->firstOrFail();
        return new GuideResource($guide);
    }

    public function update($guideID, array $data)
    {
        $guide = Guide::withoutGlobalScope(ActiveScope::class)->findOrFail($guideID);
        $guide->update($data);
        return $guide->load(['languages', 'categories', 'feedbacks']);

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
        return GuideResource::collection(Guide::guideWithRate()
                                        ->orderByDesc('rating')
                                        ->paginate($this->count));
    }

    public function relatedGuides(Guide $guide)
    {
        return GuideResource::collection(Guide::where('city_id', '=', $guide->city_id)
                                                ->where('id', '!=', $guide->id)
                                                ->guideWithRate()
                                                ->paginate($this->count));
    }

    public function trashedGuides()
    {
        return GuideResource::collection(Guide::withoutGlobalScope(ActiveScope::class)
                                                ->onlyTrashed()
                                                ->guideWithRate()
                                                ->paginate($this->count));
    }
}
