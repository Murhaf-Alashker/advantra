<?php

namespace App\Http\Resources;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdmin = $this->messageable_type == Guide::class;
        return [
            'message' => $this->message,
            'media' => $this->media,
            'isAdmin' => $isAdmin,
            'forCurrentUser' => ($isAdmin && Auth::guard('api-guide')->check()) || ($this->messageable_id == Auth::guard('api-user')->id()),
            'user' => $isAdmin ? new GuideResource(Guide::find($this->messageable_id)) : new UserResource(User::find($this->messageable_id)),
            'created_at' => $this->created_at

        ];
    }
}
