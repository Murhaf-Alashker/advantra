<?php

namespace App\Http\Resources;

use App\Http\Controllers\EventController;
use App\Libraries\FileManager;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isNull;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $path = EventService::FILE_PATH . $this->id;
        $media = $this->getMedia($path);
        $hasOffer = $this->hasOffer();

        $locale = App::getLocale();

        if ($locale == 'ar') {
            $this->name = $this->translate('name');
            $this->description = $this->translate('description');
        }

        $forUser = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'rate' => $this->rating ?? '0',
            'price' =>$hasOffer? $this->price - $this->offer()->first() : $this->price,
            'status' => $this->status ,
            'reviewer_count' => $this->reviewer_count ,
            'has offer' => $this->hasOffer(),
            'city' => $this->whenLoaded('city', fn () => new CityResource($this->city)),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'images' => $media['images'] ?? [],
            'videos' => $media['videos'] ?? [],

        ];

        $moreInfo = [
            'basic_cost' => $this->basic_cost ?? '0',
            'stars_count' => $this->stars_count ,
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
