<?php

namespace App\Libraries;

use App\Http\Resources\GuideResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Guide;
use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GuideSearchClass extends SearchClass
{
    /**
     * Create a new class instance.
     */
    protected array $cities = [];
    protected array $categories = [];
    protected array $languages = [];
    public function __construct()
    {
        parent::__construct();
    }

    public function setCities(array $cities = []): void
    {
        $this->cities = empty($cities) ? City::all()->pluck('id')->toArray() : $cities;
    }

    public function setLanguages(array $languages = []): void
    {
        $this->languages = empty($languages) ? Language::all()->pluck('id')->toArray() : $languages;
    }

    public function setCategories(array $categories = []): void
    {
        $this->categories = empty($categories) ? Category::all()->pluck('id')->toArray() : $categories;
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
        $guides = $this->prepare(Guide::query());
        return GuideResource::collection($guides->where('status', $this->status)
                                              ->whereIn('city_id', $this->cities)
                                              ->whereHas('languages', function ($query) {$query->whereIn('languages.id', $this->languages);})
                                              ->whereHas('categories', function ($query) {$query->whereIn('categories.id', $this->categories);})
                                              ->get()
        );
    }



}
