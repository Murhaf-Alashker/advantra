<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class FeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = User::with('media')->findOrFail($this->user_id);
        $forUser = [
            'rating' => $this->rating,
            'comment' => $this->comment,
            'date' => $this->updated_at,
            'user' => new UserResource($user),
        ];

        $moreInfo = [
            'created_at' => $this->created_at,
        ];
        if(Auth::guard('api-user')->check()) {
            return $forUser;
        }

        if(Auth::guard('api-admin')->check()) {
            return array_merge($forUser, $moreInfo);
        }

        return [];
    }
}
