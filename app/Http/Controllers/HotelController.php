<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index()
    {
        $hotels = Hotel::all();
        foreach ($hotels as $hotel) {
            $hotel->info = json_decode($hotel->info);
        }
        return $hotels;
    }
}
