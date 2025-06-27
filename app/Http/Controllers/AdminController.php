<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function login(AdminLoginRequest $request)
    {
        $user = Admin::first();
        if(!$user || !Hash::check( $request->password , $user->password )){
            return response()->json(['message' => __('message.wrong_email_or_password')]);
        }

        $token = $user->createToken('user_token',['api-admin'])->plainTextToken;

        return response()->json(['message' => __('message.login_successfully'), 'token' => $token]);
    }
}
