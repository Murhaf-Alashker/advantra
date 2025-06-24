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

        $local = App::getLocale();

        if($local == 'ar'){
            $this->name = $this->translate('name');
            $this->description = $this->translate('description');
        }

        $forUser = [
            'id' => $this->id,
            'name' => $this->name,
            'started_date' => $this->starting_date,
            'ended_at' => $this->ending_date,
            'rate' => $this->rating ?? '0',
            'price' => $this->price,
            'tickets_count' => $this->tickets_count,
            'feedbacks' => FeedbackResource::collection($this->whenLoaded('feedbacks')),
            'events' => EventResource::collection($this->whenLoaded('events')),
            'guide' => $this->whenLoaded('guide', fn() => new GuideResource($this->guide)),
            'cities' => CityResource::collection($this->whenLoaded('cities')),
            'images' => $this->whenLoaded('media', function () use ($path) {
                return FileManager::bringMedia($this->media , $path);
            })
        ];

        $moreInfo = [
            'stars_count' => $this->stars_count,
            'reviews_count' => $this->reviews_count,
            'guide_id' => $this->guide_id,
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
