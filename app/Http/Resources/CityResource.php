<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
          'id'=>$this->id,
          'name'=>$this->name,
          'description'=>$this->description,
          'status'=>$this->status,
          'images'=> MediaResource::collection($this->whenLoaded('media')),
          'language'=>$this->whenLoaded('language' ,fn() => new LanguageResource($this->language)),
          'country'=>$this->whenLoaded('country' , fn() => new CountryResource($this->country)),
        ];
    }
}
