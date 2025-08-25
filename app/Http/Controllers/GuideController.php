<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckResetPasswordCodeRequest;
use App\Http\Requests\CreateGuideRequest;
use App\Http\Requests\GuideCheckResetPasswordCodeRequest;
use App\Http\Requests\GuideResetPasswordRequest;
use App\Http\Requests\LogInGuideRequest;
use App\Http\Requests\resetPasswordRequest;
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
use App\Models\User;
use App\Services\GuideService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Mail\VerificationCodeMail;

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

    public function requestResetPasswordCode(Request $request)
    {
        if($request->email)
        {
            $validated = $request->validate([
                'email' => ['required','string','max:30','min:15','email','exists:guides,email'],
            ]);
            $email = $validated['email'];
            $guide = Guide::where('email' , $email)->first();
        }
        else
        {
            $guide=Auth::guard('api-guide')->user();
            $email = $guide->email;
        }
        $requestAble = $this->requestAble($email);

        if(!$requestAble)
        {
            return response()->json(['message' => __('message.rate_limit_exceeded')], ResponseAlias::HTTP_TOO_MANY_REQUESTS);
        }
        try{
            $exist = DB::table('password_reset_tokens')->where('email' , $email)->first();
            $code = (string) rand(100000, 999999);

            DB::transaction(function () use ($email,$code,$exist){
                if($exist){
                    DB::table('password_reset_tokens')
                        ->where('email' , $email)
                        ->update([
                            'code' => $code,
                            'expired_at' => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                        ]);
                }
                else{
                    DB::table('password_reset_tokens')->insert([
                        'email' => $email,
                        'code' => $code,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        'expired_at' => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                    ]);}
            });

            $this->sendVerificationCodeMail($code, $guide->name, $guide->email);

            return response()->json(['message' => __('message.send_verify_code')], ResponseAlias::HTTP_OK);
        }
        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function checkResetPasswordCode(GuideCheckResetPasswordCodeRequest $request)
    {
        $data = $request->validated();
        $info = DB::table('password_reset_tokens')->where('email',$data['email'])->first();
        if($info && $info->code == $data['code']){
            if(Carbon::parse($info->expired_at)->lessThan(Carbon::now())){
                return response()->json(['message' => __('message.expired_code'), 'data' => false],ResponseAlias::HTTP_GONE);
            }
            return response()->json(true, ResponseAlias::HTTP_OK);
        }
        return response()->json(false, ResponseAlias::HTTP_BAD_REQUEST);
    }

    public function resetPasswordUsingCode(GuideResetPasswordRequest $request)
    {
        $data = $request->validated();
        $info = DB::table('password_reset_tokens')->where('email',$data['email'])->first();
        if(!$info){
            return response()->json(['message' => __('message.invalid_email')], ResponseAlias::HTTP_BAD_REQUEST);
        }
        if($info->code != $data['code']){
            return response()->json(['message' => __('message.invalid_code')], ResponseAlias::HTTP_BAD_REQUEST);
        }
        if (Carbon::parse($info->expired_at)->lessThan(Carbon::now())) {
            return response()->json(['message' => __('message.expired_code')], ResponseAlias::HTTP_GONE);
        }
        $guide = Guide::where('email',$data['email'])->first();
        $guide->password = Hash::make($data['password']);
        $guide->save();
        DB::table('password_reset_tokens')->where('email',$data['email'])->delete();
        return response()->json(['message' => __('message.reset_password_successfully')], ResponseAlias::HTTP_OK);
    }

    private function requestAble(string $email):bool
    {
        try {
            $key = "verify_code_to:{$email}";
            Cache::forget($key);
            $requestCount = Cache::get($key) ?? 0;

            if ($requestCount >= 5) {
                return false;
            }

            if ($requestCount == 0) {
                Cache::put($key, 0, now()->addDays(1));
            }

            Cache::increment($key);

            return true;
        }
        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function sendVerificationCodeMail(string $verifyCode, string $name, string $email):void
    {
        try {
            Mail::to($email)->queue(new VerificationCodeMail($verifyCode, $name));
        }

        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
