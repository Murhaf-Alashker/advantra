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
        return Auth::guard('api-user')->user();
    }

    public function updateInfo(array $data)
    {
        $user = Auth::guard('api-user')->user();
        if(!$user){
            return response()->json(['message' => __('message.something_wrong')], 400);
        }
        $user->update($data);
        $user->updateMedia(self::FILE_PATH);
        return response()->json(['message' => __('message.updated_successfully',['attribute' => __('message.attributes.info')])], 200);
    }
}
