<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Cookie\CookieJar;
use App\Http\Controllers\Controller;

//TODO(MAYBE): split this into micropub endpoint and micropub client

class MicropubController extends Controller
{
    /**
     * Display the new notes form
     *
     * @param  \Illuminate\Cookie\CookieJar $cookie
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function micropubNewNotePage(CookieJar $cookie, Request $request)
    {
        $authed = false;
        $url = '';
        $syndication = [];
        $syndicationType = null;
        $errorMessage = false;
        if ($request->session()->has('error-message')) {
            $errorMessage = session('error-message');
        }
        if ($request->cookie('me') && $request->cookie('me') != 'loggedout') {
            $authed = true;
            $url = $request->cookie('me');
            $lastChecked = $request->cookie('token_last_verified');
            $then = Carbon::createFromFormat('Y-m-d', $lastChecked, 'Europe/London');
            $diff = $then->diffInDays(Carbon::now());
            if ($diff >= 31) {
                $valid = $this->checkTokenValidity($request->cookie('token'));
                if ($valid == true) {
                    $cookie->queue('token_last_verified', date('Y-m-d'), 86400);
                }
            }
            $syndicationTargets = $request->cookie('syndication');
            if ($syndicationTargets) {
                $syndicateTo = [];
                $mpSyndicateTo = [];
                $parts = explode(';', $syndicationTargets);
                foreach ($parts as $part) {
                    $target = explode('=', $part);
                    if ($target[0] == 'syndicate-to') {
                        $syndicateTo[] = urldecode($target[1]);
                    } elseif ($target[0] == 'mp-syndicate-to') {
                        $mpSyndicateTo[] = urldecode($target[1]);
                    }
                }
                if (count($mpSyndicateTo) != 0) {
                    $syndication = $mpSyndicateTo;
                    $syndicationType = 'mp';
                } elseif (count($syndicateTo) != 0) {
                    $syndication = $syndicateTo;
                }
            }
        }
        return view('micropubnewnotepage', array('authed' => $authed, 'url' => $url, 'errorMessage' => $errorMessage, 'syndication' => $syndication, 'syndicationType' => $syndicationType));
    }

    /**
     * Post the notes content to the relavent micropub API endpoint
     *
     * @todo   make sure this works with multiple syndication targets
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        $replyTo = $request->input('in-reply-to');
        $note = $request->input('content');

        $domain = $request->cookie('me');
        $token = $request->cookie('token');

        $micropubEndpoint = \IndieAuth\Client::discoverMicropubEndpoint($domain);
        if (!$micropubEndpoint) {
            return redirect('notes/new')->with('error', 'Unable to determine micropub API endpoint');
        }

        $guzzle = new \GuzzleHttp\Client();
        $guzzlerequest = $guzzle->createRequest('POST', $micropubEndpoint);
        $guzzlerequest->setHeader('Authorization', 'Bearer ' . $token);
        $postBody = $guzzlerequest->getBody();
        $postBody->setField('h-entry', '');
        $postBody->setField('content', $note);
        if ($replyTo != '') {
            $postBody->setField('in-reply-to', $replyTo);
        }
        if ($request->input('mp-syndicate-to')) {
            foreach ($request->input('mp-syndicate-to') as $syn) {
                $postBody->setField('mp-syndicate-to[]', $syn);
            }
        } elseif ($request->input('syndicate-to')) {
            foreach ($request->input('syndicate-to') as $syn) {
                $postBody->setField('syndicate-to[]', $syn);
            }
        }
        if ($request->input('confirmlocation')) {
            $latLng = $request->input('location');
            $geoURL = 'geo:' . str_replace(' ', '', $latLng);
            $postBody->setField('location', $geoURL);
            if ($request->input('address') != '') {
                $postBody->setField('place_name', $request->input('address'));
            }
        }
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = $photo->getClientOriginalName();
            $photo->move(storage_path(), $filename);
            $postBody->addFile(new \GuzzleHttp\Post\PostFile('photo', fopen(storage_path() . '/' . $filename, 'r')));
        }
        try {
            $response = $guzzle->send($guzzlerequest);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            if (isset($filename)) {
                unlink(storage_path() . '/' . $filename);
            }
            return redirect('notes/new')->with('error', 'There was a bad response from the micropub endpoint.');
        }
        if (isset($filename)) {
            unlink(storage_path() . '/' . $filename);
        }

        if ($response->getStatusCode() == 201) {
            $location = (string)$response->getHeader('Location');
            return redirect($location);
        }
        return $response;
    }

    /**
     * If the user has auth’d via IndieAuth, issue a valid token
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function tokenEndpoint(Request $request)
    {
        $me = $request->input('me');
        $authorizationEndpoint = \IndieAuth\Client::discoverAuthorizationEndpoint($me);
        if ($authorizationEndpoint) {
            $code = $request->input('code');
            $redirectUri = $request->input('redirect_uri');
            $clientId = $request->input('client_id');
            $state = $request->input('state');
            $auth = \IndieAuth\Client::verifyIndieAuthCode($authorizationEndpoint, $code, $me, $redirectUri, $clientId, $state);
            if (array_key_exists('me', $auth)) {
                $scope = array_key_exists('scope', $auth) ? $auth['scope'] : '';
                $scopes = explode(' ', $scope);
                $tokensController = new TokensController();
                $token = $tokensController->saveToken($auth['me'], $clientId, $scopes);

                $content = http_build_query(array(
                    'me' => $me,
                    'scopes' => $scopes,
                    'access_token' => $token
                ));
                return (new Response($content, 200))
                               ->header('Content-Type', 'application/x-www-form-urlencoded');
            }
            $contents = 'There was an error verifying the authorisation code. Sorry.';
            return (new Response($contents, 400));
        }
        $contents = 'There was an error discovering the authorisation endpoint.';
        return (new Response($contents, 400));
    }

    /**
     * This function receives an API request, verifies the authenticity
     * then passes over the info to the AdminController
     *
     * @param  \Illuminate\Http\Request request
     * @return \Illuminate\Http\Response
     */
    public function note(Request $request)
    {
        $httpAuth = $request->header('Authorization');
        if (preg_match('/Bearer (.+)/', $httpAuth, $match)) {
            $token = $match[1];

            $tokensController = new TokensController();

            //$scope = 'post';
            $tokenData = $tokensController->tokenValidity($token);
            if ($tokenData === false) {
                $tokenData = array('scopes' => array());
            } //this is a quick hack so the next line doesn't error out

            if (in_array('post', $tokenData['scopes'])) { //this may need double checking
                $clientId = $tokenData['client_id'];
                $admin = new AdminController();
                $longurl = $admin->postNewNote($request, true, $client_id);
                $content = 'Note created at ' . $longurl;
                return (new Response($content, 201))
                              ->header('Location', $longurl);
            }
            $content = http_build_query(array(
                'error' => 'invalid_token',
                'error_description' => 'The token provided is not valid or does not have the necessary scope',
            ));
            return (new Response($content, 400))
                          ->header('Content-Type', 'application/x-www-form-urlencoded');
        }
        $content = 'No OAuth token sent with request.';
        return (new Response($content, 400));
    }

    /**
     * A GET request has been made to api/post with an accompanying
     * token, here we check wether the token is valid and respond
     * appropriately. Further if the request has the query parameter
     * synidicate-to we respond with the known syndication endpoints
     *
     * @todo   Move the syndication endpoints into a .env variable
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getEndpoint(Request $request)
    {
        $httpAuth = $request->header('Authorization');
        if (preg_match('/Bearer (.+)/', $httpAuth, $match)) {
            $token = $match[1];

            $tokensController = new TokensController();
            $valid = $tokensController->tokenValidity($token);

            if ($valid === false) {
                $content = 'Invalid token';
                return (new Response($content, 400));
            }
            //we have a valid token, is `syndicate-to` set?
            if ($request->input('q') === 'syndicate-to') {
                $content = http_build_query(array(
                    'syndicate-to' => 'twitter.com/jonnybarnes',
                    'mp-syndicate-to' => 'twitter.com/jonnybarnes',
                ));
                return (new Response($content, 200))
                              ->header('Content-Type', 'application/x-www-form-urlencoded');
            }
            //nope, just return the token
            $content = http_build_query(array(
                'me' => $valid['me'],
                'scopes' => $valid['scopes'],
                'client_id' => $valid['client_id']
            ));
            return (new Response($content, 200))
                          ->header('Content-Type', 'application/x-www-form-urlencoded');
        }
        $content = 'No OAuth token sent with request.';
        return (new Response($content, 400));
    }

    /**
     * Check the token is still a valid token
     *
     * @param  string The token
     * @return bool
     */
    public function checkTokenValidity($token)
    {
        $tokensController = new TokensController();

        if ($tokensController->tokenValidity($token) === false) {
            return false;
        }
        return true; //we don't want to return the token data, just bool
    }

    /**
     * We make a request to the micropub endpoint requesting syndication targets
     * and store these in a cookie.
     *
     * @param  \Illuminate\Cookie\CookieJar $cookie
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Routing\Redirector redirect
     */
    public function refreshSyndicationTargets(CookieJar $cookie, Request $request)
    {
        $domain = $request->cookie('me');
        $token = $request->cookie('token');
        $micropubEndpoint = \IndieAuth\Client::discoverMicropubEndpoint($domain);

        if (!$micropubEndpoint) {
            return redirect('notes/new')->with('error', 'Unable to determine micropub API endpoint');
        }

        $client = new \GuzzleHttp\Client();
        $guzzleRequest = $client->createRequest('GET', $micropubEndpoint);
        $guzzleRequest->setHeader('Authorization', 'Bearer ' . $token);
        $query = $guzzleRequest->getQuery();
        $query['q'] = 'syndicate-to';

        try {
            $response = $client->send($guzzleRequest);
        } catch (\GuzzleHttp\Exception\BadResponsetException $e) {
            return redirect('notes/new')->with('error-message', 'Bad response when refreshing syndication targets');
        }
        $body = (string) $response->getBody();
        $syndication = str_replace(['&', '[]'], [';', ''], $body);

        $cookie->queue('syndication', $syndication, 44640);
        return redirect('notes/new');
    }
}
