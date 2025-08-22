<?php

namespace App\Libraries;

use App\Http\Resources\CityResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Language;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class CitySearchClass extends SearchClass
{
    /**
     * Create a new class instance.
     */
    protected array $languages = [];
    protected array $countries = [];
    protected array $ignore_order = ['price','rating','starting_date','ending_date'];

    public function __construct()
    {
        parent::__construct();
    }

    public function setLanguages(array $languages = []): void
    {
        $this->languages = empty($languages) ? Language::all()->pluck('id')->toArray() : $languages;
    }

    public function setCountries(array $countries = []): void
    {
        $this->countries = empty($countries) ? Country::all()->pluck('id')->toArray() : $countries;
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
        $cities = City::query();
        $cities = $this->checkOrderType($cities);
        $cities = strlen($this->contains ?? '') > 0 ? $cities->where('name', 'like', '%'.$this->contains.'%')
            ->orWhere('description', 'like', '%'.$this->contains.'%')
            : $cities ;
        return CityResource::collection($cities->where('status', $this->status)
                                                ->whereIn('language_id', $this->languages)
                                                ->whereIn('country_id', $this->countries)
                                                ->get()
        );
    }

    public function checkOrderType(Builder $query) :Builder
    {
        if(!in_array($this->orderBy, $this->ignore_order)){
            return $query->orderBy($this->orderBy,$this->order_type);
        }
        return $query;
    }
}
