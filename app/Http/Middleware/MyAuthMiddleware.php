<?php namespace App\Http\Middleware;

use Closure;

class MyAuthMiddleware {

	/**
	 * Check the user is logged in
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		if(session('loggedin') !== true) {
			//they’re not logged in, so send them to login form
			return redirect()->route('login');
		}

		return $next($request);
	}

}
