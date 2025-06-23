<?php

namespace App\Http\Resources;

use App\Libraries\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class GuideResource extends JsonResource
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
            $this->description = $this->translate('description');
        }
        $path = 'guides/' . $this->id;
        $fileManager = new FileManager();
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            //'email'           => $this->email,
            //'phone'           => $this->phone,
            'description'     => $this->description,
            //'card_number'     => $this->card_number,
            //'status'          => $this->status,
            'price'           => $this->price,
            //'const_salary'    => $this->const_salary,
            //'extra_salary'    => $this->extra_salary,
            'stars_count'     => $this->stars_count,
            'reviews_count'   => $this->reviews_count,
            //'fcm_token'       => $this->fcm_token,
            'city'            => new CityResource($this->whenLoaded('city')),
            'languages'        => LanguageResource::collection($this->whenLoaded('languages')),
            'images' => $this->whenLoaded('media', function () use ($fileManager, $path) {
                return $this->media->map(function ($media) use ($fileManager, $path) {
                    $url = $fileManager->upload($path, $media->path);
                    return [
                        'id' => $media->id,
                        'url' => $url,
                    ];
                });
            })

        ];
    }
}
