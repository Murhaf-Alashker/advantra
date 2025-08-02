<?php

namespace App\Http\Resources;

use App\Libraries\FileManager;
use App\Services\GuideService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isNull;
use Illuminate\Support\Facades\App;

class GuideResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $path = GuideService::FILE_PATH ;
        $media = $this->getMedia($path);


        $forUser = [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'description' => $this->description,
            'price' => $this->price,
            'rate' => $this->rating ?? '0',
            'languages' => LanguageResource::collection($this->whenLoaded('languages')),
            'city' => $this->whenLoaded('city', fn() => new CityResource($this->city)),
            'feedbacks' => FeedbackResource::collection($this->whenLoaded('feedbacks')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'images' => $media['images'] ?? [],
            //'videos' => $media['videos'] ?? [],
            'reviews_count' => $this->reviews_count,
        ];

        $moreInfo = [
            'email' => $this->email,
            'status' => $this->status,
            'const_salary' => $this->const_salary,
            'extra_salary' => $this->extra_salary,
            'stars_count' => $this->stars_count,
            'is_deleted' => $this->deleted_at != null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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
