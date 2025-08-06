<?php

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class OfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if(Auth::guard('api-admin')->check()){
            return [
                'discount' => $this->discount,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at
            ];
        }
        return [];
    }
}
