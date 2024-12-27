<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CityResource;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    // mengambil semua data city beserta jumlah officeSpace nya
    public function index()
    {
        $cities = City::withCount('officeSpaces')->get();
        return CityResource::collection($cities);
    }

    // mengambil detail city
    public function show(City $city)
    {
        $city->load(['officeSpaces.city', 'officeSpaces.photos']); // merelasikan data city
        $city->loadCount('officeSpaces'); // menghitung jumlah office dalam 1 city 
        return new CityResource($city);
    }
}
