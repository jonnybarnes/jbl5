<?php namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Filesystem\Filesystem;

class TokensController extends Controller
{
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

    public function tokenValidity($token)
    {
        $token_data = $this->openToken($token);

        if ($token_data && $token_data['valid'] == 1) {
            return $token_data;
        } else {
            return false;
        }
    }

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

    public function deleteToken($token)
    {
        $fs = new Filesystem();
        $file = storage_path() . '/tokens/' . $token;
        $success = $fs->delete($file);

        return $success;
    }
}
