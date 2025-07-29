<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGuideRequest;
use App\Http\Requests\UpdateGuideRequest;
use App\Http\Resources\GuideResource;
use App\Libraries\FileManager;
use App\Models\Category;
use App\Models\City;
use App\Models\Guide;
use App\Models\Language;
use App\Services\GuideService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuideController extends Controller
{
    protected GuideService $guideService;
    public function __construct(GuideService $guideService)
    {
        $this->guideService = $guideService;
    }
    public function index()
    {
        return $this->guideService->index();
    }

    public function show(Guide $guide )
    {
        return $this->guideService->show($guide);
    }

    public function store(CreateGuideRequest $request)
    {
        $guide = DB::transaction(function () use ($request) {

            $validated = $request->validated();

            $data =collect($validated)->except('languages','city','categories')->all();

            $data['city_id'] = City::where('name',$validated['city'])->first()->id;

            $guide = $this->guideService->store($data);

            $languageIds = Language::whereIn('name', $validated['languages'])->pluck('id')->toArray();

            $categoriesId =Category::whereIn('name', $validated['categories'])->pluck('id')->toArray();

            $guide->languages()->sync($languageIds);

            $guide->categories()->sync($categoriesId);

            $guide->load(['languages','categories']);

            return $guide;

        });

        return response()->json(['message' => __('message.created_successfully',['attribute' => __('message.attributes.guide')]), 'guide ' => new GuideResource($guide)],201) ;
    }

    public function update(UpdateGuideRequest $request, Guide $guide)
    {
        $validated = $request->validated();

        $data =collect($validated)->except('media','languages','categories')->all();

        if(isset($validated['languages']))
        {
            $languageIds =Language::whereIn('name', $validated['languages'])->pluck('id')->toArray();

            $guide->languages()->sync($languageIds);
        }

        if(isset($validated['categories']))
        {
            $categoriesId =Category::whereIn('name', $validated['categories'])->pluck('id')->toArray();

            $guide->categories()->sync($categoriesId);
        }

        if($request->hasFile('image'))
        {
            $fileName = $this->guideService->updateMedia($guide, $request->file('image'));
        }

        return $this->guideService->update($guide, $data);
    }

    public function destroy(Guide $guide)
    {

        $exists = $guide->groupTrips()->notFinished()->exists();

        if($exists)
        {
            return response()->json(['message' => __('message.cannot_delete_guide_with_active_group')], 400);
        }

         $deleted = $this->guideService->destroy($guide);

        return response()->json(['message' => __('message.deleted_successfully',['attribute' => 'message.attributes.guide'])], 204);
    }

    public function relatedGuides(Guide $guide)
    {
        return $this->guideService->relatedGuides($guide);
    }
}
