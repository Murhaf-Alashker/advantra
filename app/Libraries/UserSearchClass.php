<?php

namespace App\Libraries;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserSearchClass extends SearchClass
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function setStatus(string $status = 'active'): void
    {
        if(Auth::guard('api-admin')->check()){
            if(in_array($status, ['active','inactive'])){
                $this->status = $status;
            }
        }
    }

    public function search()
    {
        $user = User::query();
        $user = strlen($this->contains ?? '') > 0 ? $user->where('name', 'like', '%'.$this->contains.'%')
            ->orWhere('email', 'like', '%'.$this->contains.'%')
            : $user ;
        return UserResource::collection($user->where('status', '=',$this->status)
                                             ->get());
    }
}
