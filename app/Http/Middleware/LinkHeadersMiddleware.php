<?php

namespace App\Http\Middleware;

use Closure;

class LinkHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->header('Link', '<https://' . config('url.longurl') . '/webmention>; rel="webmention"', false)
                 ->header('Link', '<https://' . config('url.longurl') . '/api/post>; rel="micropub"', false);

        return $response;
    }
}