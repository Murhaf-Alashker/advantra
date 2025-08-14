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
        $request->validate([
            'year' => ['date_format:Y','size:4','min:2020', 'max:' . carbon::now()->year],
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
        return response()->json([
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ]);
    }
}
