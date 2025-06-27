<?php

namespace App\Services;



use App\Http\Resources\GuideResource;
use App\Libraries\FileManager;
use App\Models\GroupTrip;
use App\Models\Guide;
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
        return GuideResource::collection(Guide::with(['media'])
                                        ->activeGuides()
                                        ->guideWithRate()
                                        ->paginate($this->count));
    }

    public function show(Guide $guide)
    {
        $guide=Guide::where('id', '=', $guide->id)
            ->guideWithRate()
            ->with(['media',
            'city',
            'languages',
            'categories',
            'feedbacks' => fn ($query) =>
            $query->whereHas('user', fn ($userQuery) =>
            $userQuery->where('status', 'active'))])
        ->firstOrFail();
        return new GuideResource($guide);
    }

    public function update(Guide $guide, array $data)
    {
        $guide->update($data);
        return new GuideResource($guide->fresh(['media','languages','categories']));
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
        return GuideResource::collection(Guide::with(['media'])
                                        ->activeGuides()
                                        ->guideWithRate()
                                        ->orderByDesc('rating')
                                        ->paginate($this->count));
    }

    public function relatedGuides(Guide $guide)
    {
        return GuideResource::collection(Guide::where('city_id', '=', $guide->city_id)
                                                ->where('id', '!=', $guide->id)
                                                ->activeGuides()
                                                ->with(['media'])
                                                ->guideWithRate()
                                                ->paginate($this->count));
    }

    public function trashedGuides()
    {
        return GuideResource::collection(Guide::with(['media'])
                                                ->onlyTrashed()
                                                ->guideWithRate()
                                                ->paginate($this->count));
    }

    public function updateMedia(Guide $guide, $file): string
    {
        $media = $guide->media()->first();

        if($media){
            $oldFileName = $media->path;
            $fileName = FileManager::updateSinglePic(self::FILE_PATH.$guide->id, $oldFileName, $file);
            $media->update(['path' => $fileName]);
        }
        else{
            $fileName = FileManager::store(self::FILE_PATH.$guide->id, $file);
            $guide->media()->create(['path' => $fileName]);
        }
        return $fileName;
    }
}
