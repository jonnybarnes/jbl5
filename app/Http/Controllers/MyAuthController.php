<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MyAuthController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Auth Controller
	|--------------------------------------------------------------------------
	|
	| Let’s login our admin user.
	|
	*/

	/**
	 * Check the login details are correct
	 */
	public function login(Request $request)
	{
		$postedName = $request->input('username');
		$postedPass = $request->input('password');

		if($postedName == env('ADMIN_USER') && $postedPass == env('ADMIN_PASS')) {
			session(['loggedin' => true]);
			return redirect('admin');
		} else {
			return redirect()->route('login');
		}
	}

}
