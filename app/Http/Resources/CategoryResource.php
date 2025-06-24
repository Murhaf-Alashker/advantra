<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = App::getLocale();

        if ($locale == 'ar') {
            $this->name = $this->translate('name');
        }
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'events' => EventResource::collection($this->whenLoaded('events')),
            'guides' => GuideResource::collection($this->whenLoaded('guides')),
        ];
    }
}
