<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Services\CountryService;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    protected CountryService $countryService;
    public function __construct(CountryService $countryService){
        $this->countryService = $countryService;
    }

    public function index(){
        return $this->countryService->index();
    }

    public function update(Request $request,Country $country)
    {
        $validated= $request->validate([
        'status' => 'required|in:active,inactive'
    ]);
        return $this->countryService->update($validated,$country);

    }
}
