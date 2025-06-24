<?php

namespace App\Http\Resources;

use App\Libraries\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isNull;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $path = 'users/' . $this->id;

        $forUser = [
            'id' => $this->id,
            'name' => $this->name,
            'images' => $this->whenLoaded('media', function () use ($path) {
                return FileManager::bringMedia($this->media , $path);
            })
        ];

        $moreInfo = [
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'status' => $this->status,
            'points' => $this->points,
            'is_deleted' => $this->deleted_at != null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
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
