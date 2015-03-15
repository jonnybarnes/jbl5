<?php namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
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
        $fs = new Filesystem();
        $tokens = $fs->files(storage_path() . '/tokens/');
        foreach ($tokens as $token) {
            $token_data = $fs->get($token);
            $tmp = json_decode($token_data);
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
     * @param  string  $client_id  The PAI client that requested the token
     * @param  array   £scopes The reuested scopes for the token
     * @return string  The name of the token
     */
    public function saveToken($me, $client_id, array $scopes)
    {
        $fs = new Filesystem();

        $random = openssl_random_pseudo_bytes(32);
        $hex = bin2hex($random);
        $path = storage_path() . '/tokens/' . $hex;
        
        $date_issued = date('Y-m-d H:i:s');

        $content = array(
            'me' => $me,
            'client_id' => $client_id,
            'scopes' => $scopes,
            'date_issued' => $date_issued,
            'valid' => 1
        );
        $json = json_encode($content);

        $fs->put($path, $json);

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
        $token_data = $this->openToken($token);

        if ($token_data && $token_data['valid'] == 1) {
            return $token_data;
        } else {
            return false;
        }
    }

    /**
     * Reead and return the token data for a supplied token name.
     *
     * @param  string The token anme
     * @return mixed
     */
    public function openToken($token)
    {
        $fs = new Filesystem();
        $file = storage_path() . '/tokens/' . $token;
        //check token extists
        if ($fs->exists($file)) {
            $json = $fs->get($file);
            $token_data = json_decode($json, true);
            return $token_data;
        } else {
            return false;
        }
    }

    /**
     * Delete a token from file
     *
     * @param  string The token name
     * @return bool
     */
    public function deleteToken($token)
    {
        $fs = new Filesystem();
        $file = storage_path() . '/tokens/' . $token;
        $success = $fs->delete($file);

        return $success;
    }
}
