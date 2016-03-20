<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\TokenService;
use App\Services\IndieAuthService;
use IndieAuth\Client as IndieClient;
use GuzzleHttp\Client as GuzzleClient;

class MicropubClientController extends Controller
{
    /**
     * The IndieAuth service container.
     */
    protected $indieAuthService;

    /**
     * The token service container.
     */
    protected $tokenService;

    /**
     * Inject the dependencies.
     */
    public function __construct(IndieAuthService $indieAuthService = null, TokenService $tokenService = null)
    {
        $this->indieAuthService = $indieAuthService ?? new IndieAuthService();
        $this->tokenService = $tokenService ?? new TokenService();
    }

    /**
     * Display the new notes form.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Carbon\Carbon $carbon
     * @return \Illuminate\View\Factory view
     */
    public function newNotePage(Request $request, Carbon $carbon)
    {
        $url = session('me');
        $syndication = $this->parseSyndicationTargets(session('syndication'));

        return view('micropubnewnotepage', [
            'url' => $url,
            'syndication' => $syndication,
        ]);
    }

    /**
     * Post the notes content to the relavent micropub API endpoint.
     *
     * @todo   make sure this works with multiple syndication targets
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \IndieAuth\Client $indieClient
     * @param  \GuzzleHttp\Client $guzzleClient
     * @return mixed
     */
    public function postNewNote(Request $request, IndieClient $indieClient, GuzzleClient $guzzleClient)
    {
        $domain = session('me');
        $token = session('token');

        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint(
            $domain,
            $indieClient
        );
        if (! $micropubEndpoint) {
            return redirect('notes/new')->withErrors('Unable to determine micropub API endpoint', 'endpoint');
        }

        $response = $this->postNoteRequest($request, $micropubEndpoint, $token, $guzzleClient);

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
     * @todo better handling of response regarding mp-syndicate-to
     *       and syndicate-to
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \IndieAuth\Client $indieClient
     * @param  \GuzzleHttp\Client $guzzleClient
     * @return \Illuminate\Routing\Redirector redirect
     */
    public function refreshSyndicationTargets(
        Request $request,
        IndieClient $indieClient,
        GuzzleClient $guzzleClient
    ) {
        $domain = session('me');
        $token = session('token');
        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint($domain, $indieClient);

        if (! $micropubEndpoint) {
            return redirect('notes/new')->withErrors('Unable to determine micropub API endpoint', 'endpoint');
        }

        try {
            $response = $guzzleClient->get($micropubEndpoint, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['q' => 'syndicate-to'],
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return redirect('notes/new')->withErrors('Bad response when refreshing syndication targets', 'endpoint');
        }
        $body = (string) $response->getBody();
        $syndication = str_replace(['&', '[]'], [';', ''], $body);

        session(['syndication' => $syndication]);

        return redirect('notes/new');
    }

    /**
     * Check the token is still a valid token.
     *
     * @param  string The token
     * @return bool
     */
    public function checkTokenValidity($token)
    {
        if ($this->tokenService->tokenValidity($token) === false) {
            return false;
        }

        return true; //we don't want to return the token data, just bool
    }

    /**
     * This method performs the actual POST request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string The Micropub endpoint to post to
     * @param  string The token to authenticate the request with
     * @param  \GuzzleHttp\Client $client
     * @return \GuzzleHttp\Response $response | \Illuminate\RedirectFactory redirect
     */
    private function postNoteRequest(
        Request $request,
        $micropubEndpoint,
        $token,
        GuzzleClient $client
    ) {
        $multipart = [
            [
                'name' => 'h',
                'contents' => 'entry',
            ],
            [
                'name' => 'content',
                'contents' => $request->input('content'),
            ],
        ];
        if ($request->hasFile('photo')) {
            $photos = $request->file('photo');
            foreach ($photos as $photo) {
                $filename = $photo->getClientOriginalName();
                $photo->move(storage_path() . '/media-tmp', $filename);
                $multipart[] = [
                    'name' => 'photo[]',
                    'contents' => fopen(storage_path() . '/media-tmp/' . $filename, 'r'),
                ];
            }
        }
        if ($request->input('in-reply-to') != '') {
            $multipart[] = [
                'name' => 'in-reply-to',
                'contents' => $request->input('reply-to'),
            ];
        }
        if ($request->input('mp-syndicate-to')) {
            foreach ($request->input('mp-syndicate-to') as $syn) {
                $multipart[] = [
                    'name' => 'mp-syndicate-to',
                    'contents' => $syn,
                ];
            }
        }
        if ($request->input('confirmlocation')) {
            $latLng = $request->input('location');
            $geoURL = 'geo:' . str_replace(' ', '', $latLng);
            $multipart[] = [
                'name' => 'location',
                'contents' => $geoURL,
            ];
            if ($request->input('address') != '') {
                $multipart[] = [
                    'name' => 'place_name',
                    'contents' => $request->input('address'),
                ];
            }
        }
        $headers = [
            'Authorization' => 'Bearer ' . $token,
        ];
        try {
            $response = $client->post($micropubEndpoint, [
                'multipart' => $multipart,
                'headers' => $headers,
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $this->cleanUpTmp();

            return redirect('notes/new')->withErrors('There was a bad response from the micropub endpoint.', 'endpoint');
        }
        $this->cleanUpTmp();

        return $response;
    }

    /**
     * Create a new place.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \IndieAuth\Client $indieClient
     * @param  \GuzzleHttp\Client $guzzleClient
     * @return mixed
     */
    public function postNewPlace(Request $request, IndieClient $indieClient, GuzzleClient $guzzleClient)
    {
        $domain = session('me');
        $token = session('token');

        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint($domain, $indieClient);
        if (! $micropubEndpoint) {
            return (new Response(json_encode([
                'error' => true,
                'message' => 'Could not determine the micropub endpoint.',
            ]), 400))
            ->header('Content-Type', 'application/json');
        }

        $place = $this->postPlaceRequest($request, $micropubEndpoint, $token, $guzzleClient);
        if ($place === false) {
            return (new Response(json_encode([
                'error' => true,
                'message' => 'Unable to create the new place',
            ]), 400))
            ->header('Content-Type', 'application/json');
        }

        return (new Response(json_encode([
            'url' => $place,
            'name' => $request->input('place-name'),
            'latitude' => $request->input('place-latitude'),
            'longitude' => $request->input('place-longitude'),
        ]), 200))
        ->header('Content-Type', 'application/json');
    }

    /**
     * Actually make a micropub request to make a new place.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string The Micropub endpoint to post to
     * @param  string The token to authenticate the request with
     * @param  \GuzzleHttp\Client $client
     * @return \GuzzleHttp\Response $response | \Illuminate\RedirectFactory redirect
     */
    private function postPlaceRequest(
        Request $request,
        $micropubEndpoint,
        $token,
        GuzzleClient $guzzleClient
    ) {
        $formParams = [
            'h' => 'card',
            'name' => $request->input('place-name'),
            'description' => $request->input('place-description'),
            'geo' => 'geo:' . $request->input('place-latitude') . ',' . $request->input('place-longitude'),
        ];
        $headers = [
            'Authorization' => 'Bearer ' . $token,
        ];
        try {
            $response = $guzzleClient->request('POST', $micropubEndpoint, [
                'form_params' => $formParams,
                'headers' => $headers,
            ]);
        } catch (ClientException $e) {
            //not sure yet...
        }
        if ($response->getStatusCode() == 201) {
            return $response->getHeader('Location')[0];
        }

        return false;
    }

    /**
     * Make a request to the micropub endpoint requesting any nearby places.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \IndieAuth\Client $indieClient
     * @param  \GuzzleHttp\Client $guzzleClient
     * @param  string $latitude
     * @param  string $longitude
     * @return \Illuminate\Http\Response
     */
    public function nearbyPlaces(
        Request $request,
        IndieClient $indieClient,
        GuzzleClient $guzzleClient,
        $latitude,
        $longitude
    ) {
        $domain = session('me');
        $token = session('token');
        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint($domain, $indieClient);

        if (! $micropubEndpoint) {
            return;
        }

        try {
            $response = $guzzleClient->get($micropubEndpoint, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['q' => 'geo:' . $latitude . ',' . $longitude],
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return;
        }

        return (new Response($response->getBody(), 200))
                ->header('Content-Type', 'application/json');
    }

    /**
     * Delete all the files in the temporary media folder.
     *
     * @return void
     */
    private function cleanUpTmp()
    {
        $files = glob(storage_path() . '/media-tmp/*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Parse the syndication targets retreived from a cookie, to a form that can
     * be used in a view.
     *
     * @param  string $syndicationTargets
     * @return array|null
     */
    private function parseSyndicationTargets($syndicationTargets = null)
    {
        if ($syndicationTargets === null) {
            return;
        }
        $mpSyndicateTo = [];
        $parts = explode(';', $syndicationTargets);
        foreach ($parts as $part) {
            $target = explode('=', $part);
            $mpSyndicateTo[] = urldecode($target[1]);
        }
        if (count($mpSyndicateTo) > 0) {
            return $mpSyndicateTo;
        }
    }
}
