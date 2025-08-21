<?php

namespace App\Libraries;

use App\Http\Resources\EventResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\Language;
use Illuminate\Support\Facades\Auth;

class EventSearchClass extends SearchClass
{
    /**
     * Create a new class instance.
     */
    protected bool $hasOffer = false;
    protected array $cities = [];
    protected array $categories = [];
    public function __construct()
    {
        parent::__construct();
    }

    public function setCategories(array $categories = []): void
    {
        $this->categories = empty($categories) ? Category::all()->pluck('id')->toArray() : $categories;
    }

    public function setCities(array $cities = []): void
    {
        $this->cities = empty($cities) ? City::all()->pluck('id')->toArray() : $cities;
    }

    public function withOffer(bool $hasOffer = false): void
    {
        $this->hasOffer = $hasOffer;

    }

    public function setStatus(string $status = 'active'): void
    {
        if(Auth::guard('api-admin')->check()){
            if(in_array($status, ['active','inactive'])){
                $this->status = $status;
            }
        }
    }

    public function search()
    {
        $events = $this->prepare(Event::query());
        $events =$this->hasOffer ? $events->hasOffer() : $events;
        return EventResource::collection($events->whereIn('city_id', $this->cities)
                                                ->where('status' ,'=' ,$this->status)
                                                ->whereHas('category', function ($query) {$query->whereIn('id', $this->categories);})
                                                ->get());
    }
}
