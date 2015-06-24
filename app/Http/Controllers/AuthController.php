<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Cookie\CookieJar;
use IndieAuth\Client;
use Session;

class AuthController extends Controller
{
    /**
     * Log in a user, set a sesion variable, check credentials against
     * the .env file.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function login(Request $request)
    {
        $postedName = $request->input('username');
        $postedPass = $request->input('password');

        if ($postedName == env('ADMIN_USER') && $postedPass == env('ADMIN_PASS')) {
            session(['loggedin' => true]);
            return redirect('admin');
        } else {
            return redirect()->route('login');
        }
    }

    /**
     * Begin the indie auth process. Here we query the user’s homepage
     * for their authorisation endpoint, and redirect them there with a
     * unique secure state value.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Routing\RedirecTResponse redirect
     */
    public function beginauth(Request $request)
    {
        $domain = $request->input('me');
        if (substr($domain, 0, 4) !== 'http') {
            $domain = 'http://' . $domain;
        }
        $authorizationEndpoint = Client::discoverAuthorizationEndpoint($domain);

        if ($authorizationEndpoint) {
            $bytes = openssl_random_pseudo_bytes(16);
            $hex = bin2hex($bytes);
            session(['state' => $hex]);
            $redirectURL = 'https://' . config('url.longurl') . '/auth';
            $clientId = 'https://' . config('url.longurl') . '/notes/new';
            $state = $hex;
            $scope = 'post';
            $authorizationURL = Client::buildAuthorizationURL($authorizationEndpoint, $domain, $redirectURL, $clientId, $state, $scope);

            return redirect($authorizationURL);
        } else {
            return redirect('notes/new')->with('error', 'Unable to determine authorisation endpoint.');
        }
    }


    /**
     * Once they have verified themselves through the authorisation endpint
     * the next step is retreiveing a token from the token endpoint. here
     * we request a token and then save it in a cookie on the user’s browser.
     *
     * @param  \Illuminate\Cookie\CookieJar $cookie
     * @param  \Illuminate\Http\Rrequest $request
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function indieauth(CookieJar $cookie, Request $request)
    {
        $me = $request->input('me');
        $code = $request->input('code');
        $stateInput = $request->input('state');
        $stateSession = session('state');
        if ($stateInput != $stateSession) {
            return redirect('notes/new')->with('error', 'Mismatch of <code>state</code> value from indieauth server.');
        }

        $redirectURL = 'https://' . config('url.longurl') . '/auth';
        $clientId = 'https://' . config('url.longurl') . '/notes/new';

        $tokenEndpoint = Client::discoverTokenEndpoint($me);

        if ($tokenEndpoint) {
            $token = Client::getAccessToken($tokenEndpoint, $code, $me, $redirectURL, $clientId, $stateInput);

            if (array_key_exists('access_token', $token)) {
                $cookie->queue('me', $token['me'], 86400);
                $cookie->queue('token', $token['access_token'], 86400);
                $cookie->queue('token_last_verified', date('Y-m-d'), 86400);

                return redirect('/notes/new');
            }
            return redirect('notes/new')->with('error', 'Error getting token from the token endpoint');
        }
        return redirect('notes/new')->with('error', 'Unable to discover your token endpoint');
    }

    /**
     * Log out the user, flush an session data, and overwrite any cookie data
     *
     * @param  \Illuminate\Cookie\CookieJar $cookie
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function indieauthLogout(CookieJar $cookie)
    {
        Session::flush();
        $cookie->queue('me', 'loggedout', 5);

        return redirect('/notes/new');
    }
}
