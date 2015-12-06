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
        $latitude = $place->getLatitude();
        $longitude = $place->getLongitude();

        return view('admin.editplace', [
            'id' => $placeId,
            'name' => $place->name,
            'description' => $place->description,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function postNewPlace(Request $request)
    {
        //we should check if this is a micropub request
        $micropub = ($request->path() == 'api/post') ? true : false;
        //we’ll either have latitude and longitude sent seperately (/admin)
        //or together on a geo-link (micropub)
        if ($request->input('geo') !== null) {
            $parts = explode(':', $request->input('geo'));
            $latlng = explode(',', $parts[1]);
            $latitude = $latlng[0];
            $longitude = $latlng[1];
        }
        if ($request->input('latitude') !== null) {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
        }
        $place = new Place();
        $place->name = $request->input('name');
        $place->description = $request->input('description');
        $place->location = new Point((float) $latitude, (float) $longitude);
        try {
            $place->save();
        } catch (PDOException $e) {
            if ($micropub) {
                return;
            }

            return back()->withInput();
        }
        if ($micropub) {
            $slug = Place::where('name', $place->name)->value('slug');

            return 'https://' . config('url.longurl') . '/places/' . $slug;
        }

        return view('admin.newplacesuccess');
    }

    public function postEditPlace($placeId, Request $request)
    {
        $place = Place::findOrFail($placeId);
        $place->name = $request->name;
        $place->description = $request->description;
        $place->location = new Point((float) $request->latitude, (float) $request->longitude);
        $place->save();

        return view('admin.editplacesuccess');
    }
}
