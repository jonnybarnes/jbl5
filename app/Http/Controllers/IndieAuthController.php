<?php

namespace App\Http\Controllers;

use IndieAuth\Client;
use Illuminate\Http\Request;
use Illuminate\Cookie\CookieJar;

class IndieAuthController extends Controller
{
    /**
     * Begin the indie auth process. Here we query the user’s homepage
     * for their authorisation endpoint, and redirect them there with a
     * unique secure state value.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \IndieAuth\Client $client
     * @return \Illuminate\Routing\RedirecTResponse redirect
     */
    public function beginauth(Request $request, Client $client)
    {
        $domain = $request->input('me');
        if (substr($domain, 0, 4) !== 'http') {
            $domain = 'http://' . $domain;
        }
        $authEndpoint = $client->discoverAuthorizationEndpoint($domain);

        if ($authEndpoint) {
            $bytes = openssl_random_pseudo_bytes(16);
            $hex = bin2hex($bytes);
            session(['state' => $hex]);
            $redirectURL = 'https://' . config('url.longurl') . '/auth';
            $clientId = 'https://' . config('url.longurl') . '/notes/new';
            $state = $hex;
            $scope = 'post';
            $authorizationURL = $client->buildAuthorizationURL(
                $authEndpoint,
                $domain,
                $redirectURL,
                $clientId,
                $state,
                $scope
            );

            return redirect($authorizationURL);
        }

        return redirect('notes/new')->with('error', 'Unable to determine authorisation endpoint.');
    }

    /**
     * Once they have verified themselves through the authorisation endpint
     * the next step is retreiveing a token from the token endpoint. here
     * we request a token and then save it in a cookie on the user’s browser.
     *
     * @param  \Illuminate\Cookie\CookieJar $cookie
     * @param  \Illuminate\Http\Rrequest $request
     * @param  \IndieAuth\Client $client
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function indieauth(CookieJar $cookie, Request $request, Client $client)
    {
        if (session('state') != $request->input('state')) {
            return redirect('notes/new')->with('error', 'Mismatch of <code>state</code> value from indieauth server.');
        }

        $redirectURL = 'https://' . config('url.longurl') . '/auth';
        $clientId = 'https://' . config('url.longurl') . '/notes/new';

        $tokenEndpoint = $client->discoverTokenEndpoint($request->input('me'));

        if ($tokenEndpoint) {
            $token = $client->getAccessToken(
                $tokenEndpoint,
                $request->input('code'),
                $request->input('me'),
                $redirectURL,
                $clientId,
                $request->input('state')
            );

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
     * Log out the user, flush an session data, and overwrite any cookie data.
     *
     * @param  \Illuminate\Cookie\CookieJar $cookie
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function indieauthLogout(Request $request, CookieJar $cookie)
    {
        $request->session()->flush();
        $cookie->queue('me', 'loggedout', 5);

        return redirect('/notes/new');
    }
}
