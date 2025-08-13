<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    public function index()
    {
        return $this->userService->index();
    }

    public function show(User $user){
        return $this->userService->show($user);
    }

    public function getInfo()
    {
        return $this->userService->getInfo();
    }

    public function updateInfo(UpdateUserRequest $request)
    {
        return $this->userService->updateInfo($request->validated());
    }

}
