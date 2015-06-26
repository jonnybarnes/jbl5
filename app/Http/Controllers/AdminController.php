<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | Here we have the logic for the admin cp
    |
     */

    /**
     * Set variables
     *
     * @var string
     */
    public function __construct()
    {
        $this->username = env('ADMIN_USER');
    }

    /**
     * Show the main admin CP page
     *
     * @return \Illuminate\View\Factory view
     */
    public function showWelcome()
    {
        return view('admin.welcome', array('name' => $this->username));
    }
}
