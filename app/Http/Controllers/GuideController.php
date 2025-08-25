<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGuideRequest;
use App\Http\Requests\LogInGuideRequest;
use App\Http\Requests\UpdateGuideProfileRequest;
use App\Http\Requests\UpdateGuideRequest;
use App\Http\Resources\CityResource;
use App\Http\Resources\GuideResource;
use App\Http\Resources\LanguageResource;
use App\Mail\GuideWelcomeMail;
use App\Models\Category;
use App\Models\City;
use App\Models\Guide;
use App\Models\Language;
use App\Models\Scopes\ActiveScope;
use App\Services\GuideService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    public function show(Guide $guide)
    {
        return $this->guideService->show($guide);
    }

    public function store(CreateGuideRequest $request)
    {
//        $guide = DB::transaction(function () use ($request) {
//
//            $validated = $request->validated();
//
//            $data =collect($validated)->except('languages','city','categories')->all();
//
//            $data['city_id'] = City::where('name',$validated['city'])->first()->id;
//
//            $guide = $this->guideService->store($data);
//
//            $languageIds = Language::whereIn('name', $validated['languages'])->pluck('id')->toArray();
//
//            $categoriesId =Category::whereIn('name', $validated['categories'])->pluck('id')->toArray();
//
//            $guide->languages()->sync($languageIds);
//
//            $guide->categories()->sync($categoriesId);
//
//            $guide->load(['languages','categories']);
//
//            return $guide;
//
//        });
           $validated = $request->validated();

           $guideData = collect($validated)->except('languages','categories')->all();
           $unHashedPassword = Str::random(10);
           $guideData['password'] = Hash::make($unHashedPassword);
           $guide = $this->guideService->store($guideData);
           $guide->languages()->sync($validated['languages']);
           $guide->categories()->sync($validated['categories']);
           Mail::to($guide->email)->queue(new GuideWelcomeMail($guide, $unHashedPassword));

        return response()->json(['message' => __('message.created_successfully',['attribute' => __('message.attributes.guide')]), 'guide ' => new GuideResource($guide)],201) ;
    }

    public function update(UpdateGuideRequest $request, Guide $guide)
    {
        $validated = $request->validated();

        $guide =  $this->guideService->update($guide,$validated);

        return new GuideResource($guide->fresh(['languages', 'categories', 'feedbacks','city',]));

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

    public function onlyTrashedGuides()
    {
        return $this->guideService->trashedGuides();
    }

    public function logIn(LogInGuideRequest $request)
    {
       $validated = $request->validated();
       $guide = Guide::where('email', $validated['email'])->first();
       if (!$guide) {
           throw ValidationException::withMessages([
               'email'=>'the provided credentials are not correct'
           ]);
       }
       if(!Hash::check($validated['password'], $guide->password)){
           throw ValidationException::withMessages([
               'password'=>'the provided credentials are not correct'
           ]);
       }
       $token = $guide->createToken('user_token',['api-guide'])->plainTextToken;

       return response()->json([
           'message' => __('message.login_successfully'),
           'token' => $token,
       ]);
    }

    public function logOut(){
       // auth()->guard('api-guide')->user()->currentAccessToken()->delete();
          Auth::guard('api-guide')->user()->tokens()->delete();
        return response()->json(['message' => __('message.logout_successfully')]);
    }

   public function getProfile()
   {

       $guide = Guide::with(['languages','city','categories'])
           ->guideWithRate()
           ->findOrFail(Auth::guard('api-guide')->id());
       return new GuideResource($guide);

   }

   public function updateProfile(UpdateGuideProfileRequest $request){
        $validated = $request->validated();
       $guide = Guide::with(['languages','city','categories'])
           ->guideWithRate()
           ->findOrFail(Auth::guard('api-guide')->id());
       $guideDate = collect($validated)->except('media','languages','categories')->all();
       $guide->update($guideDate);
       if(isset($validated['languages'])){
           $guide->languages()->sync($validated['languages']);
       }
       if(isset($validated['categories'])){
           $guide->categories()->sync($validated['categories']);
       }
       if(isset($validated['media'])){
           $guide->updateMedia(GuideService::FILE_PATH);
       }
       return new GuideResource($guide);

   }
}
