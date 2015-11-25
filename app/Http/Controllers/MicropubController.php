<?php

namespace App\Http\Controllers;

use IndieAuth\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MicropubController extends Controller
{
    /**
     * If the user has auth’d via IndieAuth, issue a valid token.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \IndieAuth\Client $client
     * @return \Illuminate\Http\Response
     */
    public function tokenEndpoint(Request $request, Client $client)
    {
        $authEndpoint = $client->discoverAuthorizationEndpoint($request->input('me'));
        if ($authEndpoint) {
            $auth = $client->verifyIndieAuthCode(
                $authEndpoint,
                $request->input('code'),
                $request->input('me'),
                $request->input('redirect_uri'),
                $request->input('client_id'),
                $request->input('state')
            );
            if (array_key_exists('me', $auth)) {
                $scope = array_key_exists('scope', $auth) ? $auth['scope'] : '';
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
            $contents = 'There was an error verifying the authorisation code. Sorry.';

            return (new Response($contents, 400));
        }
        $contents = 'There was an error discovering the authorisation endpoint.';

        return (new Response($contents, 400));
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
            $tokensController = new TokensController();
            $tokenData = $tokensController->tokenValidity($token);
            if ($tokenData === false) {
                $tokenData = ['scopes' => []];
            } //this is a quick hack so the next line doesn't error out

            if (in_array('post', $tokenData['scopes'])) { //this may need double checking
                $clientId = $tokenData['client_id'];
                $type = $request->input('h');
                switch ($type) {
                    case 'entry':
                        $admin = new NotesAdminController();
                        $longurl = $admin->postNewNote($request, $clientId);
                        $content = 'Note created at ' . $longurl;

                        return (new Response($content, 201))
                                      ->header('Location', $longurl);
                        break;
                    case 'card':
                        $admin = new PlacesAdminController();
                        $longurl = $admin->postNewPlace($request);
                        $content = 'Place created at ' . $longurl;

                        return (new Response($content, 201))
                                      ->header('Location', $longurl);
                        break;
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

        return (new Response($content, 400));
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

            $tokensController = new TokensController();
            $valid = $tokensController->tokenValidity($token);

            if ($valid === false) {
                return (new Response('Invalid token', 400));
            }
            //we have a valid token, is `syndicate-to` set?
            if ($request->input('q') === 'syndicate-to') {
                $content = http_build_query([
                    'mp-syndicate-to' => 'twitter.com/jonnybarnes',
                ]);

                return (new Response($content, 200))
                              ->header('Content-Type', 'application/x-www-form-urlencoded');
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

        return (new Response($content, 400));
    }
}
