<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use App\Libraries\FileManager;
use App\Models\Admin;
use App\Models\City;
use App\Models\Media;
use App\Rules\ValidPoints;
use App\Services\AdminService;
use App\Services\CityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    protected AdminService  $adminService;
    public function __construct(AdminService  $adminService)
    {
        $this->adminService = $adminService;
    }
    public function login(AdminLoginRequest $request)
    {
        $user = Admin::first();
        if(!$user || !Hash::check( $request->password , $user->password )){
            return response()->json(['message' => __('message.wrong_email_or_password')],401);
        }

        $token = $user->createToken('user_token',['api-admin'])->plainTextToken;

        return response()->json(['message' => __('message.login_successfully'), 'token' => $token],201);
    }

    public function businessInfo(Request $request)
    {
        $request->validate([
            'year' => ['integer','min:2020', 'max:' . carbon::now()->year],
        ]);
        return $this->adminService->businessPage($request->input('year') ?? Carbon::now()->year);
    }

    public function citiesDashboard(Request $request)
    {
        $request->validate([
            'page' => ['nullable','min:1'],
            'orderBy' => ['nullable','in:rate,visitor,name,revenue,events,guides'],
            'q' => ['nullable','string','min:1','max:100'],
        ]);
        $paginator = $this->adminService->cityPage(
            $request->input('page') ?? 1,
                  $request->input('orderBy') ?? 'name',
                  $request->input('q')
        );
        $data = $paginator->items();
        foreach ($data as &$item) {
            $item->country = City::where('id','=',$item->id)->first()->country->name;
            $item->images = [];
            $item->videos = [];
            $allmedia = Media::where('mediable_type','=','App\Models\City')
                            ->where('mediable_id','=',$item->id)
                            ->get();
            foreach ($allmedia as $media) {
             $path = CityService::FILE_PATH.$item->id.'/'.$media->type.'/';
             $url = FileManager::upload($path,$media->path);
                $item->{$media->type}[] = ['id' => $media->id,'url'=>$url[0]];
            }

        }
        return response()->json([
            'data' => $paginator->items(),
            'avg_rate' => $this->adminService->totalRate(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ]);
    }

    public function getCitiesAndCategoriesAndLanguageIds()
    {
        return $this->adminService->getCitiesAndCategoriesAndLanguageIds();
    }

    public function sendGift(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required','integer','exists:users,id'],
            'points' => ['required','integer',new ValidPoints],
        ]);
        return $this->adminService->sendGift($data);
    }

    public function eventsAndGroups()
    {
        return $this->adminService->eventsAndGroupsPage();
    }

    public function guides(Request $request)
    {
        $request->validate([
            'order_type' => ['nullable','in:ASC,DESC'],
            'per_page' => ['nullable','integer','min:1','max:100'],
        ]);
        return $this->adminService->guideForAdmin($request->input('order_type') ?? 'DESC',$request->input('per_page') ?? 20);
    }
}
