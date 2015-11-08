<?php

namespace App\Http\Controllers;

use App\Place;

class PlacesAdminController extends Controller
{
    public function newPlace()
    {
        return view('admin.newplace');
    }
}
