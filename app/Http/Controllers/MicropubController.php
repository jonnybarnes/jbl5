<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Cookie;
use Carbon\Carbon;

//TODO(MAYBE): split this into micropub endpoint and micropub client

class MicropubController extends Controller
{
    /**
     * Display the new notes form
     *
     * @return \Illuminate\View\Factory view
     */
    public function micropubNewNotePage()
    {
        $authed = false;
        $url = '';
        $syndication = [];
        if (session('error')) {
            $error = session('error');
        } else {
            $error = false;
        }
        if (Cookie::get('me') && Cookie::get('me') != 'loggedout') {
            $authed = true;
            $url = Cookie::get('me');
            $lastChecked = Cookie::get('token_last_verified');
            $then = Carbon::createFromFormat('Y-m-d', $lastChecked, 'Europe/London');
            $diff = $then->diffInDays(Carbon::now());
            if ($diff >= 31) {
                $valid = $this->checkTokenValidity(Cookie::get('token'));
                if ($valid == true) {
                    Cookie::queue('token_last_verified', date('Y-m-d'), 86400);
                } else {
                    $error = 'Unable to verify if the current token is still valid';
                }
            }
            $syndicationTargets = Cookie::get('syndication');
            if ($syndicationTargets) {
                $parts = explode(';', $syndicationTargets);
                foreach ($parts as $part) {
                    $target = explode('=', $part);
                    $syndication[] = urldecode($target[1]);
                }
            }
        }
        return view('micropubnewnotepage', array('authed' => $authed, 'url' => $url, 'error' => $error, 'syndication' => $syndication));
    }

    /**
     * Post the notes content to the relavent micropub API endpoint
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        $replyTo = $request->input('in-reply-to');
        $note = $request->input('content');

        $domain = Cookie::get('me');
        $token = Cookie::get('token');

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
        if ($request->input('twitter')) {
            $postBody->setField('syndicate-to', 'twitter.com');
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
        } else {
            return $response;
        }
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
            $redirect_uri = $request->input('redirect_uri');
            $client_id = $request->input('client_id');
            $state = $request->input('state');
            $auth = \IndieAuth\Client::verifyIndieAuthCode($authorizationEndpoint, $code, $me, $redirect_uri, $client_id, $state);
            if (array_key_exists('me', $auth)) {
                $scope = array_key_exists('scope', $auth) ? $auth['scope'] : '';
                $scopes = explode(' ', $scope);
                $t = new TokensController();
                $token = $t->saveToken($auth['me'], $client_id, $scopes);

                $content = http_build_query(array(
                    'me' => $me,
                    'scopes' => $scopes,
                    'access_token' => $token
                ));
                return (new Response($content, 200))
                               ->header('Content-Type', 'application/x-www-form-urlencoded');
            } else {
                $contents = 'There was an error verifying the authorisation code. Sorry.';
                return (new Response($contents, 400));
            }
        } else {
            $contents = 'There was an error discovering the authorisation endpoint.';
            return (new Response($contents, 400));
        }
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

            $t = new TokensController();

            $scope = 'post';
            $token_data = $t->tokenValidity($token);
            if ($token_data === false) {
                $token_data = array('scopes' => array());
            } //this is a quick hack so the next line doesn't error out

            if (in_array('post', $token_data['scopes'])) { //this may need double checking
                $client_id = $token_data['client_id'];
                $admin = new AdminController();
                $longurl = $admin->postNewNote($request, true, $client_id);
                $content = 'Note created at ' . $longurl;
                return (new Response($content, 201))
                              ->header('Location', $longurl);
            } else {
                $content = http_build_query(array(
                    'error' => 'invalid_token',
                    'error_description' => 'The token provided is not valid or does not have the necessary scope',
                ));
                return $response;
                return (new Response($content, 400))
                              ->header('Content-Type', 'application/x-www-form-urlencoded');
            }
        } else {
            $content = 'No OAuth token sent with request.';
            return (new Response($content, 400));
        }
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

            $t = new TokensController();
            $valid = $t->tokenValidity($token);

            if ($valid === false) {
                $content = 'Invalid token';
                return (new Response($content, 400));
            } else {
                //we have a valid token, is `syndicate-to` set?
                if ($request->input('q') === 'syndicate-to') {
                    $content = http_build_query(array(
                        'syndicate-to' => 'twitter.com/jonnybarnes',
                    ));
                    return (new Response($content, 200))
                                  ->header('Content-Type', 'application/x-www-form-urlencoded');
                } else {
                    //nope, just return the token
                    $content = http_build_query(array(
                        'me' => $valid['me'],
                        'scopes' => $valid['scopes'],
                        'client_id' => $valid['client_id']
                    ));
                    return (new Response($content, 200))
                                  ->header('Content-Type', 'application/x-www-form-urlencoded');
                }
            }
        } else {
            $content = 'No OAuth token sent with request.';
            return (new Response($content, 400));
        }
    }

    /**
     * We make a request to the micropub endpoint requesting syndication targets
     * and store these in a cookie.
     *
     * @return \Illuminate\Routing\Redirector redirect
     */
    public function refreshSyndicationTargets()
    {
        $domain = Cookie::get('me');
        $token = Cookie::get('token');
        $micropubEndpoint = \IndieAuth\Client::discoverMicropubEndpoint($domain);

        if (!$micropubEndpoint) {
            return redirect('notes/new')->with('error', 'Unable to determine micropub API endpoint');
        }

        $client = new \GuzzleHttp\Client();
        $request = $client->createRequest('GET', $micropubEndpoint);
        $request->setHeader('Authorization', 'Bearer ' . $token);
        $query = $request->getQuery();
        $query['q'] = 'syndicate-to';

        try {
            $response = $client->send($request);
        } catch (\GuzzleHttp\Exception\BadResponsetException $e) {
            return redirect('notes/new')->with('error', 'Bad response when refreshing syndication targets');
        }
        $body = (string) $response->getBody();
        $syndication = str_replace(['&', '[]'], [';', ''], $body);
        
        Cookie::queue('syndication', $syndication);
        return redirect('notes/new');
    }
}
