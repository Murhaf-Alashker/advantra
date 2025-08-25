<?php

namespace App\Http\Resources;

use App\Enums\Status;
use App\Libraries\FileManager;
use App\Models\Guide;
use App\Models\Scopes\ActiveScope;
use App\Services\GroupTripService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class GroupTripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $path = GroupTripService::FILE_PATH;
        $media = $this->getMedia($path);
        $hasOffer = $this->hasOffer();
        $locale = App::getLocale();
        $name_ar = $this->translate('name');
        $description_ar = $this->translate('description');

        $forUser = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'starting_date' => $this->starting_date,
            'ending_date' => $this->ending_date,
            'rate' => $this->rating ?? '0',
            'status' => $this->status,
            'price' => $hasOffer? round($this->price * ((100 - $this->offers()->first()->discount) / 100)) : $this->price,
            'remaining_tickets' => $this->remaining_tickets,
            'has_offer' => $hasOffer,
            'feedbacks' => FeedbackResource::collection($this->whenLoaded('feedbacks')),
            'events' => EventResource::collection($this->whenLoaded('events')),
            'guide' => new GuideResource($this->guide),
            'images' => $media['images'] ?? [],
            'videos' => $media['videos'] ?? [],
            'cities' => $this->cities(),
            'reviewer_count' => $this->reviewer_count,
        ];

        $moreInfo = [
            'name_ar' => $name_ar,
            'description_ar' => $description_ar,
            'stars_count' => $this->stars_count,
            'tickets_count' => $this->tickets_count,
            'tickets_limit' => $this->tickets_limit,
            'basic_cost' => $this->basic_cost,
            'extra_cost' => $this->extra_cost,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        if(Auth::guard('api-user')->check()||Auth::guard('api-guide')->check()) {
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
            $allData['revenue'] = $this->status === Status::FINISHED->value ? $this->revenue() : 0;

            return $allData;
        }

        return [];
    }
}
