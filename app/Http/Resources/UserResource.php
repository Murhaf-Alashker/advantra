<?php

namespace App\Http\Resources;

use App\Libraries\FileManager;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isNull;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $path = UserService::FILE_PATH ;
        $media = $this->getMedia($path);

        $forUser = [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $media['images'] ?? [],
        ];

        $moreInfo = [
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'status' => $this->status,
            'points' => $this->points,
            'is_deleted' => $this->deleted_at != null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
        if(Auth::guard('api-user')->check()) {
            return $forUser;
        }

        if(Auth::guard('api-admin')->check()) {
            $moreInfo['reserved_events'] = $this->allEvents(Carbon::now()->format('Y-m-d'));
            $moreInfo['reserved_groups'] = $this->groupTrips(Carbon::now()->format('Y-m-d'));
            $moreInfo['reserved_solo_tips'] = $this->soloTrips()->whereMonth('created_at', '=', Carbon::now()->month)->whereYear('created_at', '=', Carbon::now()->year)->get();
            $moreInfo['gifted_points'] = $this->gifted_points ?? 0;

            if(isset($this->events_reserved_tickets)){
                $moreInfo['events_reserved_tickets'] = $this->events_reserved_tickets;
            }
            if(isset($this->group_trip_reserved_tickets) ){
                $moreInfo['group_trip_reserved_tickets'] = $this->group_trip_reserved_tickets;
            }

            return array_merge($forUser, $moreInfo);
        }

        return [];
    }
}
