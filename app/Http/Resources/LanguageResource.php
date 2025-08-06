<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = App::getLocale();

        $forUser = [
          'id' => $this->id,
          'name' => $this->name
        ];

        $moreInfo = [
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if(Auth::guard('api-user')->check()) {
            if ($locale == 'ar') {
                $forUser['name'] = $this->translate('name');
            }
            return $forUser;
        }

        if(Auth::guard('api-admin')->check()) {
            return array_merge($forUser, $moreInfo);
        }

        return [];
    }
}
