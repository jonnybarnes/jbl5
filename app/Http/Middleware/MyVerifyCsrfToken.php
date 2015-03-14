<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as VerifyCsrfToken;

class MyVerifyCsrfToken extends VerifyCsrfToken
{
    /**
     * The URLs we don’t want to verify CSRF tokens for
     *
     * @var array
     */
    protected $excluded_urls = [
        'api/token',
        'api/post',
        'webmention'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $regex = '#' . implode('|', $this->excluded_urls) . '#';
        if ($this->isReading($request) || $this->tokensMatch($request) ||
                preg_match($regex, $request->path())) {
            return $this->addCookieToResponse($request, $next($request));
        }
        throw new TokenMismatchException;
    }
}
