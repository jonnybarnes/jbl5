<?php

namespace App\Http\Controllers;

use App\Place;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\TokenService;

class MicropubController extends Controller
{
    /**
     * The token service container.
     */
    protected $tokenService;

    /**
     * Injest the dependency.
     */
    public function __construct(TokenService $tokenService = null)
    {
        $this->tokenService = $tokenService ?? new TokenService();
    }

    /**
     * This function receives an API request, verifies the authenticity
     * then passes over the info to the relavent AdminController.
     *
     * @param  \Illuminate\Http\Request request
     * @return \Illuminate\Http\Response
     */
    public function post(Request $request)
    {
        $httpAuth = $request->header('Authorization');
        if (preg_match('/Bearer (.+)/', $httpAuth, $match)) {
            $token = $match[1];
            $tokenData = $this->tokenService->tokenValidity($token);
            if ($tokenData === false) {
                $tokenData = ['scopes' => []];
            } //this is a quick hack so the next line doesn't error out

            if (in_array('post', $tokenData['scopes'])) { //this may need double checking
                $clientId = $tokenData['client_id'];
                $type = $request->input('h');
                if ($type == 'entry') {
                    $admin = new NotesAdminController();
                    $longurl = $admin->postNewNote($request, $clientId);
                    $content = 'Note created at ' . $longurl;

                    return (new Response($content, 201))
                                      ->header('Location', $longurl);
                }
                if ($type == 'card') {
                    $admin = new PlacesAdminController();
                    $longurl = $admin->postNewPlace($request);
                    if ($longurl === null) {
                        return (new Response(json_encode([
                            'error' => true,
                            'message' => 'Unable to create place.',
                        ]), 400))->header('Content-Type', 'application/json');
                    }
                    $content = 'Place created at ' . $longurl;

                    return (new Response($content, 201))
                                      ->header('Location', $longurl);
                }
            }
            $content = http_build_query([
                'error' => 'invalid_token',
                'error_description' => 'The token provided is not valid or does not have the necessary scope',
            ]);

            return (new Response($content, 400))
                          ->header('Content-Type', 'application/x-www-form-urlencoded');
        }
        $content = 'No OAuth token sent with request.';

        return new Response($content, 400);
    }

    /**
     * A GET request has been made to api/post with an accompanying
     * token, here we check wether the token is valid and respond
     * appropriately. Further if the request has the query parameter
     * synidicate-to we respond with the known syndication endpoints.
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
            $valid = $this->tokenService->tokenValidity($token);

            if ($valid === false) {
                return new Response('Invalid token', 400);
            }
            //we have a valid token, is `syndicate-to` set?
            if ($request->input('q') === 'syndicate-to') {
                $content = http_build_query([
                    'mp-syndicate-to' => 'twitter.com/jonnybarnes',
                ]);

                return (new Response($content, 200))
                              ->header('Content-Type', 'application/x-www-form-urlencoded');
            }
            //nope, how about a geo URL?
            if (substr($request->input('q'), 0, 4) === 'geo:') {
                $geo = explode(':', $request->input('q'));
                $latlng = explode(',', $geo[1]);
                $latitude = $latlng[0];
                $longitude = $latlng[1];
                $places = Place::near($latitude, $longitude, 1000);

                return (new Response($places->toJson(), 200))
                        ->header('Content-Type', 'application/json');
            }
            //nope, just return the token
            $content = http_build_query([
                'me' => $valid['me'],
                'scopes' => $valid['scopes'],
                'client_id' => $valid['client_id'],
            ]);

            return (new Response($content, 200))
                          ->header('Content-Type', 'application/x-www-form-urlencoded');
        }
        $content = 'No OAuth token sent with request.';

        return new Response($content, 400);
    }
}
