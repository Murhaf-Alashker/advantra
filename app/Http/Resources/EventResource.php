<?php

namespace App\Http\Resources;

use App\Http\Controllers\EventController;
use App\Libraries\FileManager;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
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

        $locale = App::getLocale();

        if ($locale == 'ar') {
            $this->name = $this->translate('name');
            $this->description = $this->translate('description');
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'rate' => $this->rating ?? '0',
            'ticket_price' => $this->ticket_price,
            'tickets_count' => $this->tickets_count ,
            'status' => $this->status ,
            'stars_count' => $this->stars_count ,
            'reviewer_count' => $this->reviewer_count ,
            'tickets_limit' => $this->tickets_limit,
            'city' => $this->whenLoaded('city', fn () => new CityResource($this->city)),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'images' => $this->whenLoaded('media', function () use ($path) {
                return FileManager::bringMedia($this->media , $path);
            })

        ];
    }
}
