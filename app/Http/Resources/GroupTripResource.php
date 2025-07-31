<?php

namespace App\Http\Resources;

use App\Libraries\FileManager;
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
        $path = GroupTripService::FILE_PATH . $this->id;
        $media = FileManager::bringMediaWithType($path);

        $local = App::getLocale();

        if($local == 'ar'){
            $this->name = $this->translate('name');
            $this->description = $this->translate('description');
        }

        $forUser = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'started_date' => $this->starting_date,
            'ended_at' => $this->ending_date,
            'rate' => $this->rating ?? '0',
            'basic_cost' => $this->basic_cost,
            'extra_cost' => $this->extra_cost,
            'status' => $this->status,
            'price' => $this->price,
            'tickets_count' => $this->tickets_count,
            'has offer' => $this->hasOffer(),
            'feedbacks' => FeedbackResource::collection($this->whenLoaded('feedbacks')),
            'events' => EventResource::collection($this->whenLoaded('events')),
            'guide' => $this->whenLoaded('guide', fn() => new GuideResource($this->guide)),
            'cities' => CityResource::collection($this->whenLoaded('cities')),
            'images' => $media['images'] ?? [],
            'videos' => $media['videos'] ?? [],
            'cities' => $this->cities()
        ];

        $moreInfo = [
            'stars_count' => $this->stars_count,
            'reviews_count' => $this->reviews_count,
            'tickets_limit' => $this->tickets_limit,
            'guide_id' => $this->guide_id,
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
