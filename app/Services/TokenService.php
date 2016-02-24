<?php

namespace App\Services;

use Illuminate\Filesystem\Filesystem;

class TokenService
{
    /**
     * Save token data to file and generate a random name. This
     * name is what other pople see *as* the token.
     *
     * @param  string  $domain A URL of (normally) a personal homepage
     * @param  string  $client_id The API client that requested the token
     * @param  array   $scopes The reuested scopes for the token
     * @return string  The name of the token
     */
    public function saveToken($domain, $clientId, array $scopes)
    {
        $filesystem = new Filesystem();
        $hex = bin2hex(random_bytes(32));
        $path = storage_path() . '/tokens/' . $hex;
        $json = json_encode([
            'me' => $domain,
            'client_id' => $clientId,
            'scopes' => $scopes,
            'date_issued' => date('Y-m-d H:i:s'),
            'valid' => 1,
        ]);
        $filesystem->put($path, $json);

        return $hex;
    }

    /**
     * Delete a token from file.
     *
     * @param  string The token name
     * @return bool
     */
    public function deleteToken($token)
    {
        $filesystem = new Filesystem();
        $file = storage_path() . '/tokens/' . $token;

        return $filesystem->delete($file);
    }

    /**
     * Check if a supplied token name matches any valid tokens on file.
     *
     * @param  string The toke name
     * @return mixed
     */
    public function tokenValidity($token)
    {
        $filesystem = new Filesystem();
        $file = storage_path() . '/tokens/' . $token;
        //check token extists
        if ($filesystem->exists($file)) {
            $tokenData = json_decode($filesystem->get($file), true);
        }
        if ($tokenData && $tokenData['valid'] == 1) {
            return $tokenData;
        }

        return false;
    }
}
