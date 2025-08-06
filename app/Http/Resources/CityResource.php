<?php

namespace App\Http\Resources;

use App\Libraries\FileManager;
use App\Services\CityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
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
        $path = CityService::FILE_PATH ;
        $media = $this->getMedia($path);
        $locale = App::getLocale();

        $forUser = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'images' => $media['images'] ?? [],
            'videos' => $media['videos'] ?? [],
            'language' => $this->whenLoaded('language', fn() => new LanguageResource($this->language)),
            'country' => $this->whenLoaded('country', fn() => new CountryResource($this->country)),
        ];

        $moreInfo = [
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        if(Auth::guard('api-user')->check()) {
            if ($locale == 'ar') {
                $forUser['name'] = $this->translate('name');
                $forUser['description'] = $this->translate('description');
            }
            return $forUser;
        }

        if(Auth::guard('api-admin')->check()) {
            return array_merge($forUser, $moreInfo);
        }

        return [];
    }
}
