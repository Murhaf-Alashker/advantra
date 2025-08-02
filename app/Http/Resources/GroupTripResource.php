<?php

namespace App\Http\Resources;

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
            'price' =>$hasOffer? $this->price - $this->offer()->first() : $this->price,
            'tickets_count' => $this->tickets_count,
            'has offer' => $hasOffer,
            'feedbacks' => FeedbackResource::collection($this->whenLoaded('feedbacks')),
            'events' => EventResource::collection($this->whenLoaded('events')),
            'guide' => Guide::withoutGlobalScope(ActiveScope::class)->first(),
            'images' => $media['images'] ?? [],
            'videos' => $media['videos'] ?? [],
            'cities' => $this->cities()
        ];

        $moreInfo = [
            'stars_count' => $this->stars_count,
            'reviews_count' => $this->reviews_count,
            'tickets_limit' => $this->tickets_limit,
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
