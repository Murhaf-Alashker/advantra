<?php

namespace App\Http\Resources;

use App\Libraries\FileManager;
use App\Services\CityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     *
     */
    public function toArray(Request $request): array
    {
        $path = CityService::FILE_PATH . $this->id;

        $locale = App::getLocale();

        if ($locale == 'ar') {
            $this->name = $this->translate('name');
            $this->description = $this->translate('description');
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'images' => $this->whenLoaded('media', function () use ($path) {
                return FileManager::bringMedia($this->media , $path);
            }),
            'language' => $this->whenLoaded('language', fn() => new LanguageResource($this->language)),
            'country' => $this->whenLoaded('country', fn() => new CountryResource($this->country)),
        ];
    }
}
