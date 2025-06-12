<?php

namespace App\Http\Controllers;

use App\Http\Resources\MediaResource;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function uploadImages(Request $request)
    {
        if (!is_array($request->images)) {
            $request->merge(['images' => (array) $request->images]);
        }

        $request->validate([
            'mediable_type' => 'required|string',
            'mediable_id' => 'required|integer',
            'images'=>'required',
            'images.*'=>'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $modelClassMap = [
            'event' => \App\Models\Event::class,
            'city' => \App\Models\City::class,
            'group_trip' => \App\Models\GroupTrip::class,
            'guide' => \App\Models\Guide::class,
            'user' => \App\Models\User::class,
            'solo_trip' => \App\Models\SoloTrip::class,
        ];

        $type = strtolower($request->input('mediable_type'));
        $modelClass = $modelClassMap[$type] ?? null;

        if (!$modelClass) {
            return response()->json(['error' => 'unknown model'], 422);
        }

        $model = $modelClass::findOrFail($request->input('mediable_id'));

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            if(is_array($images))
            {
                $this->mediaService->storeMany($model, $images);
            }else
            {
                $this->mediaService->store($model, $images);
            }
        }

        return response()->json([
            'message' => 'images uploaded!',
            'media' => MediaResource::collection($model->media),
            201
        ]);
    }

    public function deleteImages(Request $request){
       $validated =  $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:media,id',
        ]);
        return $this->mediaService->deleteImages($validated);
    }
}
