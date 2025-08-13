<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use App\Models\Admin;
use App\Services\AdminService;
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
        return $this ->adminService->businessPage($request->input('year') ?? Carbon::now()->year);
    }
}
