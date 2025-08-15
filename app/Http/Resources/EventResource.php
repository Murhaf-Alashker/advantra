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
        $path = EventService::FILE_PATH ;
        $media = $this->getMedia($path);
        $hasOffer = $this->hasOffer();
        $locale = App::getLocale();
        $name_ar = $this->translate('name');
        $description_ar = $this->translate('description');


        $forUser = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'rate' => $this->rating ?? '0',
            'price' => $hasOffer? round($this->price * ((100 - $this->offers()->first()->discount) / 100)) : $this->price,
            'status' => $this->status ,
            'has offer' => $this->hasOffer(),
            'city' => $this->whenLoaded('city', fn () => new CityResource($this->city)),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'feedbacks' => FeedbackResource::collection($this->whenLoaded('feedbacks')),
            'images' => $media['images'] ?? [],
            'videos' => $media['videos'] ?? [],

        ];

        $moreInfo = [
            'name_ar' =>$name_ar,
            'description_ar' =>$description_ar,
            'basic_cost' => $this->basic_cost ?? '0',
            'reviewer_count' => $this->reviewer_count,
            'stars_count' => $this->stars_count ,
            'is_deleted' => $this->deleted_at != null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
        if(Auth::guard('api-user')->check()){
            if($locale == 'ar'){
                $forUser['name'] = $name_ar;
                $forUser['description'] = $description_ar;
            }
            return $forUser;
        }

        if(Auth::guard('api-admin')->check()) {
            $allData = array_merge($forUser, $moreInfo);
            if($hasOffer){
                $allData['main_price'] = $this->price;
                $allData['offers'] = OfferResource::collection($this->offers);
            }
            return $allData;
        }

        return [];
    }
}
