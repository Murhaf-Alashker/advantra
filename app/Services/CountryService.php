<?php
namespace App\Services;


use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryService
{

    public function index()
    {
        return CountryResource::collection(Country::paginate(10));
    }

    public function update(array $data ,Country $country)
    {
         $country->update($data);
        return new CountryResource($country);
    }

}
