<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Filesystem\Filesystem;

class TokensController extends Controller
{
    /**
     * Return all the tokens
     *
     * @return array
     */
    public function getAll()
    {
        $return = array();
        $filesystem = new Filesystem();
        $tokens = $filesystem->files(storage_path() . '/tokens/');
        foreach ($tokens as $token) {
            $tokenData = $filesystem->get($token);
            $tmp = json_decode($tokenData);
            $name = last(explode('/', $token));
            $return[$name] = $tmp;
        }

        return $return;
    }

    /**
     * Save token data to file and generate a random name. This
     * name is what other pople see *as* the token.
     *
     * @param  string  $me A URL of (normally) a personal homepage
     * @param  string  $client_id  The API client that requested the token
     * @param  array   £scopes The reuested scopes for the token
     * @return string  The name of the token
     */
    public function saveToken($me, $clientId, array $scopes)
    {
        $filesystem = new Filesystem();

        $random = openssl_random_pseudo_bytes(32);
        $hex = bin2hex($random);
        $path = storage_path() . '/tokens/' . $hex;

        $dateIssued = date('Y-m-d H:i:s');

        $content = array(
            'me' => $me,
            'client_id' => $clientId,
            'scopes' => $scopes,
            'date_issued' => $dateIssued,
            'valid' => 1
        );
        $json = json_encode($content);

        $filesystem->put($path, $json);

        return $hex;
    }

    /**
     * Check if a supplied token name matches any valid tokens on file.
     *
     * @param  string The toke name
     * @return mixed
     */
    public function tokenValidity($token)
    {
        $tokenData = $this->openToken($token);

        if ($tokenData && $tokenData['valid'] == 1) {
            return $tokenData;
        }
        return false;
    }

    /**
     * Reead and return the token data for a supplied token name.
     *
     * @param  string The token anme
     * @return mixed
     */
    public function openToken($token)
    {
        $filesystem = new Filesystem();
        $file = storage_path() . '/tokens/' . $token;
        //check token extists
        if ($filesystem->exists($file)) {
            $json = $filesystem->get($file);
            return json_decode($json, true);
        }
        return false;
    }

    /**
     * Delete a token from file
     *
     * @param  string The token name
     * @return bool
     */
    public function deleteToken($token)
    {
        $filesystem = new Filesystem();
        $file = storage_path() . '/tokens/' . $token;
        $success = $filesystem->delete($file);

        return $success;
    }
}
