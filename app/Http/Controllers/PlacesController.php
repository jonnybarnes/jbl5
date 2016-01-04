<?php

namespace App\Http\Controllers;

use App\Place;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlacesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $places = Place::all();

        return view('allplaces', ['places' => $places]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $place = Place::where('slug', '=', $slug)->first();
        $place->latitude = $place->getLatitude();
        $place->longitude = $place->getLongitude();

        return view('singleplace', ['place' => $place]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Show places near a given Lat/Lng value.
     *
     * @param  float latitude
     * @param  float longitude
     * @param  int distance limit
     * @return Collection
     */
    public function nearbyPlaces($latitude, $longitude, $distance = null)
    {
        $distance = $distance ?? 1000;
        $places = Place::near($latitude, $longitude, $distance);

        return (new Response($places->toJson(), 200))
                        ->header('Content-Type', 'application/json');
    }
}
