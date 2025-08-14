<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class UserService
{
    public const FILE_PATH =  'uploads/users/';

    public function index()
    {
        return UserResource::collection(User::where('status', '=', 'active')->paginate(10));
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function getInfo()
    {
        $user = Auth::guard('api-user')->user();
        if($user){
            $user->reserved_events = $user->allEvents();
            $user->reserved_groups = $user->groupTrips();
            $user->reserved_solo_tips = (clone $user)->soloTrips;
        return $user;
        }
        return [];
    }

    public function updateInfo(array $data)
    {
        $user = Auth::guard('api-user')->user();
        if(!$user){
            return response()->json(['message' => __('message.something_wrong')], 400);
        }
        $user->update($data);
        if(isset($data['media'])){
        $user->updateMedia(self::FILE_PATH);}
        return response()->json(['message' => __('message.updated_successfully',['attribute' => __('message.attributes.info')])], 200);
    }
}
