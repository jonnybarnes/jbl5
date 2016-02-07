<?php

namespace App\Http\Controllers;

use IndieAuth\Client;
use Illuminate\Http\Request;
use Illuminate\Cookie\CookieJar;
use App\Services\IndieAuthService;

class IndieAuthController extends Controller
{
    public function __construct(IndieAuthService $indieAuthService, Client $client)
    {
        $this->indieAuthService = $indieAuthService;
        $this->indieClient = $client;
    }

    /**
     * Begin the indie auth process. This method ties in to the login page
     * from our micropub client. Here we then query the user’s homepage
     * for their authorisation endpoint, and redirect them there with a
     * unique secure state value.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function beginauth(Request $request)
    {
        $domain = $request->input('me');
        if (substr($domain, 0, 4) !== 'http') {
            $domain = 'http://' . $domain;
        }
        $authorizationEndpoint = $this->indieAuthService->getAuthorizationEndpoint($domain, $this->indieClient);
        if ($authorizationEndpoint) {
            $authorizationURL = $this->indieAuthService->buildAuthorizationURL(
                $authorizationEndpoint,
                $domain,
                $this->indieClient
            );
            if ($authorizationURL) {
                return redirect($authorizationURL);
            }
        }

        return redirect('notes/new')->withErrors('Unable to determine authorisation endpoint', 'indieauth');
    }

    /**
     * Once they have verified themselves through the authorisation endpint
     * the next step is retreiveing a token from the token endpoint. here
     * we request a token and then save it in a cookie on the user’s browser.
     *
     * @param  \Illuminate\Http\Rrequest $request
     * @param  \Illuminate\Cookie\CookieJar $cookie
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function indieauth(Request $request, CookieJar $cookie)
    {
        if (session('state') != $request->input('state')) {
            return redirect('notes/new')->withErrors(
                'Invalid <code>state</code> value returned from indieauth server',
                'indieauth'
            );
        }
        $tokenEndpoint = $this->indieAuthService->discoverTokenEndpoint($request->input('me'));
        $redirectURL = 'https://' . config('url.longurl') . '/indieauth';
        $clientId = 'https://' . config('url.longurl') . '/notes/new';
        $data = [
            'endpoint' => $tokenEndpoint,
            'code' => $request->input('code'),
            'domain' => $request->input('me'),
            'redirect_url' => $redirectURL,
            'client_id' => $clientId,
            'state' => $request->input('state'),
        ];
        $token = $this->indieAuthService->getToken($data);

        if (array_key_exists('access_token', $token)) {
            $cookie->queue('me', $token['me'], 86400);
            $cookie->queue('token', $token['access_token'], 86400);
            $cookie->queue('token_last_verified', date('Y-m-d'), 86400);

            return redirect('/notes/new');
        }

        return redirect('notes/new')->withErrors('Unable to get a token from the endpoint', 'indieauth');
    }

    /**
     * If the user has auth’d via IndieAuth, issue a valid token.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \IndieAuth\Client $client
     * @return \Illuminate\Http\Response
     */
    public function tokenEndpoint(Request $request, Client $client)
    {
        $data = [
            'code' => $request->input('code'),
            'domain' => $request->input('me'),
            'redirect_url' => $request->input('redirect_uri'),
            'client_id' => $request->input('client_id'),
            'state' => $request->input('state'),
        ];
        $auth = $this->indieAuthService->verifyIndieAuthCode($data);
        if (array_key_exists('me', $auth)) {
            $scope = $auth['scope'] ?? '';
            $scopes = explode(' ', $scope);
            $tokensController = new TokensController();
            $token = $tokensController->saveToken(
                $auth['me'],
                $request->input('client_id'),
                $scopes
            );
            $content = http_build_query([
                'me' => $request->input('me'),
                'scopes' => $scopes,
                'access_token' => $token,
            ]);

            return (new Response($content, 200))
                           ->header('Content-Type', 'application/x-www-form-urlencoded');
        }
        $content = 'There was an error verifying the authorisation code.';

        return new Response($content, 400);
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
