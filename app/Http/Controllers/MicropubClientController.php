<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Cookie\CookieJar;
use App\Http\Controllers\Controller;

class MicropubClientController extends Controller
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
        if ($request->session()->has('error')) {
            $errorMessage = session('error');
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
     * @param  \IndieAuth\Client $indieClient
     * @param  \GuzzleHttp\Client $guzzleClient
     * @return mixed
     */
    public function post(Request $request, IndieClient $indieClient, GuzzleClient $guzzleClient)
    {
        $replyTo = $request->input('in-reply-to');
        $note = $request->input('content');

        $domain = $request->cookie('me');
        $token = $request->cookie('token');

        $micropubEndpoint = $indieClient->discoverMicropubEndpoint($domain);
        if (!$micropubEndpoint) {
            return redirect('notes/new')->with('error', 'Unable to determine micropub API endpoint');
        }

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = $photo->getClientOriginalName();
            $photo->move(storage_path(), $filename);
            $multipart = [
                [
                    'name' => 'photo',
                    'contents' => fopen(storage_path() . '/' . $filename, 'r')
                ],
                [
                    'name' => 'h',
                    'contents' => 'entry'
                ],
                [
                    'name' => 'content',
                    'contents' => $note
                ]
            ];
            if ($replyTo != '') {
                $multipart[] = [
                    'name' => 'in-reply-to',
                    'contents' => $replyTo
                ];
            }
            if ($request->input('mp-syndicate-to')) {
                foreach ($request->input('mp-syndicate-to') as $syn) {
                    $multipart[] = [
                        'name' => 'mp-syndicate-to',
                        'contents' => $syn
                    ];
                }
            } elseif ($request->input('syndicate-to')) {
                foreach ($request->input('syndicate-to') as $syn) {
                    $multipart[] = [
                        'name' => 'syndicate-to',
                        'contents' => $syn
                    ];
                }
            }
            if ($request->input('confirmlocation')) {
                $latLng = $request->input('location');
                $geoURL = 'geo:' . str_replace(' ', '', $latLng);
                $multipart[] = [
                    'name' => 'location',
                    'contents' => $geoURL
                ];
                if ($request->input('address') != '') {
                    //$postBody->setField('place_name', $request->input('address'));
                    $multipart[] = [
                        'name' => 'place_name',
                        'contents' => $request->input('address')
                    ];
                }
            }
            $headers = [
                'Authorization' => 'Bearer ' . $token
            ];
            try {
                $response = $guzzleClient->post($micropubEndpoint, [
                    'multipart' => $multipart,
                    'headers' => $headers
                ]);
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                unlink(storage_path() . '/' . $filename);
                return redirect('notes/new')->with('error', 'There was a bad response from the micropub endpoint.');
            }
        } else {
            $formParams = [
                'h' => 'entry',
                'content' => $note
            ];
            if ($replyTo != '') {
                $formParams['in-reply-to'] = $replyTo;
            }
            if ($request->input('mp-syndicate-to')) {
                $mpSyndicateTo = [];
                foreach ($request->input('mp-syndicate-to') as $syn) {
                    $mpSyndicateTo[] = $syn;
                }
                $formParams['mp-syndicate-to'] = $mpSyndicateTo;
            } elseif ($request->input('syndicate-to')) {
                $syndicateTo = [];
                foreach ($request->input('syndicate-to') as $syn) {
                    $syndicateTo[] = $syn;
                }
                $formParams['syndicate-to'] = $syndicateTo;
            }
            if ($request->input('confirmlocation')) {
                $latLng = $request->input('location');
                $geoURL = 'geo:' . str_replace(' ', '', $latLng);
                $formParams['location'] = $geoURL;
                if ($request->input('address') != '') {
                    $formParams['place_name'] = $request->input('address');
                }
            }
            $headers = [
                'Authorization' => 'Bearer ' . $token
            ];
            try {
                $response = $guzzleClient->post($micropubEndpoint, [
                    'form_params' => $formParams,
                    'headers' => $headers
                ]);
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                return redirect('notes/new')->with('error', 'There was a bad response from the micropub endpoint.');
            }
        }

        if ($response->getStatusCode() == 201) {
            $location = $response->getHeader('Location');
            if (is_array($location)) {
                return redirect($location[0]);
            }
            return redirect($location);
        }
        return $response;
    }

    /**
     * We make a request to the micropub endpoint requesting syndication targets
     * and store these in a cookie.
     *
     * @param  \Illuminate\Cookie\CookieJar $cookie
     * @param  \Illuminate\Http\Request $request
     * @param  \IndieAuth\Client $indieClient
     * @param  \GuzzleHttp\Client $guzzleClient
     * @return \Illuminate\Routing\Redirector redirect
     */
    public function refreshSyndicationTargets(
        CookieJar $cookie,
        Request $request,
        IndieClient $indieClient,
        GuzzleClient $guzzleClient
    ) {
        $domain = $request->cookie('me');
        $token = $request->cookie('token');
        $micropubEndpoint = $indieClient->discoverMicropubEndpoint($domain);

        if (!$micropubEndpoint) {
            return redirect('notes/new')->with('error', 'Unable to determine micropub API endpoint');
        }

        try {
            $response = $guzzleClient->get($micropubEndpoint, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['q' => 'syndicate-to']
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return redirect('notes/new')->with('error-message', 'Bad response when refreshing syndication targets');
        }
        $body = (string) $response->getBody();
        $syndication = str_replace(['&', '[]'], [';', ''], $body);

        $cookie->queue('syndication', $syndication, 44640);
        return redirect('notes/new');
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
}
