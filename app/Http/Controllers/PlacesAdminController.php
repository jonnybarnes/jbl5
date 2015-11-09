<?php

namespace App\Http\Controllers;

use App\Place;
use Illuminate\Http\Request;
use Phaza\LaravelPostgis\Geometries\Point;

class PlacesAdminController extends Controller
{
    public function listPlaces()
    {
        $places = Place::all();

        return view('admin.listplaces', ['places' => $places]);
    }

    public function newPlace()
    {
        return view('admin.newplace');
    }

    public function editPlace($placeId)
    {
        $place = Place::findOrFail($placeId);

        $location = $place->location;
        preg_match('/\((.*?)\)/', $location, $num);
        $parts = explode(' ', $num[1]);
        $latitude = $parts[1];
        $longitude = $parts[0];

        return view('admin.editplace', [
            'id' => $placeId,
            'name' => $place->name,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function postNewPlace(Request $request)
    {
        $place = new Place();
        $place->name = $request->name;
        $place->location = new Point((float) $request->latitude, (float) $request->longitude);
        $place->save();

        return view('admin.newplacesuccess');
    }

    public function postEditPlace($placeId, Request $request)
    {
        $place = Place::findOrFail($placeId);
        $place->name = $request->name;
        $place->location = new Point((float) $request->latitude, (float) $request->longitude);
        $place->save();

        return view('admin.editplacesuccess');
    }
}
