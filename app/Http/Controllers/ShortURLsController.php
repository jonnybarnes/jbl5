<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Jonnybanres\Posse\URL;
use App\ShortURL;

class ShortURLsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Short URL Controller
    |--------------------------------------------------------------------------
    |
    |    This redirects the short urls to long ones
    |
    */

    public function baseURL()
    {
        return redirect('https://' . config('url.longurl'));
    }

    public function twitter()
    {
        return redirect('https://twitter.com/jonnybarnes');
    }

    public function googlePLus()
    {
        return redirect('https://plus.google.com/u/0/117317270900655269082/about');
    }

    public function appNet()
    {
        return redirect('https://alpha.app.net/jonnybarnes');
    }

    public function expandType($type, $id)
    {
        if ($type == 't') {
            $type = 'notes';
        }
        if ($type == 'b') {
            $type = 'blog/s';
        }

        $redirect = 'https://' . config('url.longurl') . '/' . $type .'/' . $id;

        return redirect($redirect);
    }

    public function redirect($id)
    {
        $url = new URL();
        $num = $url->b60tonum($id);
        $shorturl = ShortURL::find($num);
        $redirect = $shorturl->redirect;

        return redirect($redirect);
    }

    public function oldRedirect($id)
    {
        $filename = base_path() . '/public/assets/old-shorturls.json';
        $handle = fopen($filename, 'r');
        $contents = fread($handle, filesize($filename));
        $object = json_decode($contents);

        foreach ($object as $key => $val) {
            if ($id == $key) {
                $url = $val;
                return redirect($url);
            }
        }

        return 'This id was never used. Old redirects are lcoated at <code><a href="https://jonnybarnes.net/assets/old-shorturls.json">old-shorturls.json</a></code>';
    }
}
