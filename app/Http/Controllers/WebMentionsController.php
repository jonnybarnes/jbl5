<?php

namespace App\Http\Controllers;

use App\Note;
use Mf2\parse;
use HTMLPurifier;
use App\WebMention;
use GuzzleHttp\Client;
use HTMLPurifier_Config;
use Jonnybarnes\Posse\URL;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Jonnybarnes\WebmentionsParser\Parser;
use App\Esceptions\RemoteContentNotFound;
use Jonnybarnes\WebmentionsParser\ParsingException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WebMentionsController extends Controller
{
    /**
     * Receive and process a webmention
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Respone
     */
    public function receive(Request $request)
    {
        //first we trivially reject requets that lack all required inputs
        if (($request->has('target') !== true) || ($request->has('source') !== true)) {
            return (new Response(
                'You need both the target and source parameters',
                400
            ));
        }

        //next check the $target is valid
        $sourceURL = parse_url($request->input('source'));
        $baseURL = $sourceURL['scheme'] . '://' . $sourceURL['host'];
        $path = parse_url($request->input('target'))['path'];
        $pathParts = explode('/', $path);

        switch ($pathParts[1]) {
            case 'notes':
                //we have anote
                $noteId = $pathParts[2];
                $url = new URL();
                $realId = $url->b60tonum($noteId);
                try {
                    $note = Note::findOrFail($realId);
                } catch (ModelNotFoundException $e) {
                    return (new Response('This note doesn’t exist.', 400));
                }
                $parser = new Parser();
                try {
                    $remoteContent = $this->getRemoteContent($source);
                    $microformats = $this->parseHTML($remoteContent, $baseURL);
                    $count = WebMention::where('source', '=', $source)->count();
                    if ($count > 0) {
                        //we already have a webmention from this source
                        $webmentions = WebMention::where('source', '=', $source)->get();
                        foreach ($webmentions as $webmention) {
                            //for each one, check its a webmention for this particular target
                            if ($webmention->target == $target) {
                                //now check it still 'mentions' this target
                                //we switch for each type of mention (reply/like/repost)
                                switch ($webmention->type) {
                                    case 'reply':
                                        if ($parser->checkInReplyTo($microformats, $target) == false) {
                                            //it doesn't so delete
                                            $webmention->delete();
                                            return (new Response('The webmention has been deleted', 202));
                                        }
                                        //webmenion is still a reply, so update content
                                        try {
                                            $content = $parser->replyContent($microformats);
                                            $this->saveImage($content);
                                            $content['reply'] = $this->filterHTML($content['reply']);
                                            $content = serialize($content);
                                            $webmention->content = $content;
                                            $webmention->save();
                                            return (new Response('The webmention has been updated', 202));
                                        } catch (Exception $e) {
                                            return (new Response('There was an error parsing the content from your site', 400));
                                        }
                                        break;
                                    case 'like':
                                        if ($parser->checkLikeOf($microformats, $target) == false) {
                                            //it doesn't so delete
                                            $webmention->delete();
                                            return (new Response('The webmention has been deleted', 202));
                                        } //note we don't need to do anything if it still is a like
                                        break;
                                    case 'repost':
                                        if ($parser->checkRepostOf($microformats, $target) == false) {
                                            //it doesn't so delete
                                            $webmention->delete();
                                            return (new Response('The webmention has been deleted', 202));
                                        } //again, we don't need to do anything if it still is a repost
                                        break;
                                }//switch
                            }//if
                        }//foreach
                        return (new Response('Webmentio received', 202));
                    }//if
                    //no wemention in db so create new one
                    $webmention = new WebMention();
                    //check it is in fact a reply
                    if ($parser->checkInReplyTo($microformats, $target)) {
                        try {
                            $content = $parser->replyContent($microformats);
                            $this->saveImage($content);
                            $content['reply'] = $this->filterHTML($content['reply']);
                            $content = serialize($content);
                            $webmention->source = $source;
                            $webmention->target = $target;
                            $webmention->commentable_id = $realId;
                            $webmention->commentable_type = 'App\Note';
                            $webmention->type = 'reply';
                            $webmention->content = $content;
                            $webmention->save();
                            return (new Response('Your webmention has been saved', 202));
                        } catch (ParsingException $e) {
                            return (new Response('There was an error parsing the content from your reply', 400));
                        }
                    } elseif ($parser->checkLikeOf($microformats, $target)) {
                        //it is a like
                        try {
                            $content = $parser->likeContent($microformats);
                            $this->saveImage($content);
                            $content = serialize($content);
                            $webmention->source = $source;
                            $webmention->target = $target;
                            $webmention->commentable_id = $realId;
                            $webmention->commentable_type = 'App\Note';
                            $webmention->type = 'like';
                            $webmention->content = $content;
                            $webmention->save();
                            return (new Response('Your webmention has been saved', 202));
                        } catch (ParsingException $e) {
                            return (new Response('There was an error parsing the content from your like', 400));
                        }
                    } elseif ($parser->checkRepostOf($microformats, $target)) {
                        //it is a repost
                        try {
                            $content = $parser->repostContent($microformats);
                            $this->saveImage($content);
                            $content = serialize($content);
                            $webmention->source = $source;
                            $webmention->target = $target;
                            $webmention->commentable_id = $realId;
                            $webmention->commentable_type = 'App\Note';
                            $webmention->type = 'repost';
                            $webmention->content = $content;
                            $webmention->save();
                            return (new Response('Your webmention has been saved', 202));
                        } catch (ParsingException $e) {
                            return (new Response('There was an error parsing the content from your repost', 400));
                        }
                    }
                    return (new Response(
                        'Your webmention does not actually link to my note',
                        400
                    ));
                } catch (RemoteContentNotFound $e) {
                    return (new Response(
                      'Error retreiving the webmention',
                      400
                    ));
                }
                break;
            case 'blog':
                return (new Response(
                    'I don’t accept webmentions for blog posts yet.',
                    501
                ));
                break;
            default:
                return (new Response(
                    'Invalid request',
                    400
                ));
                break;
        }
    }

    /**
     * Send a webmention.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  The source URL on this site
     * @return array   An array of successful then failed URLs
     */
    public function send(Request $request, $source)
    {
        if ($request->input('webmenetions') == null) {
            return null;
        }

        $success = array();
        $failure = array();
        //parse reply to values
        $urls = explode(' ', $request->input('in-reply-to'));
        foreach ($urls as $url) {
            $endpoint = $this->discoverWebmentionEndpoint($url);

            if ($endpoint) {
                $target = $url;
                if ($this->sendWebmention($endpoint, $source, $target)) {
                    $success[] = $target;
                } else {
                    $failure[] = $target;
                }
            } else {
                $failure[] = $url;
            }
        }

        $return = array($success, $failure);
        return $return;
    }

    /**
     * Retreive the remote content from a URL
     *
     * @param  string  The URL to retreive content from
     * @return string  The HTML from the URL
     */
    public function getRemoteContent($url)
    {
        $client = new Client();

        $response = $client->get($url);
        $html = (string) $response->getBody();
        $path = storage_path() . '/HTML/' . $this->createFilenameFromURL($url);
        $this->fileForceContents($path, $html);

        return $html;
    }

    /**
     * Create a file path from a URL. This is ued to cache the HTML response
     *
     * @param  string  The URL
     * @return string  The path name
     */
    private function createFilenameFromURL($url)
    {
        $url = str_replace(array('https://', 'http://'), array('', ''), $url);
        if (substr($url, -1) == '/') {
            $url = $url . 'index.html';
        }

        return $url;
    }

    /**
     * Save a profile image to the local cache
     *
     * @param  array  source content
     * @return bool   wether image was saved or not (we don’t save twitter profiles)
     */
    public function saveImage($content)
    {
        $photo = $content['photo'];
        $home = $content['url'];
        //dont save pbs.twimg.com links
        if (parse_url($photo)['host'] != 'pbs.twimg.com'
              && parse_url($photo)['host'] != 'twitter.com') {
            $client = new Client();
            try {
                $response = $client->get($photo);
                $image = $response->getBody(true);
                $path = public_path() . '/assets/profile-images/' . parse_url($home)['host'] . '/image';
                $this->fileForceContents($path, $image);
            } catch (Exception $e) {
                // we are openning and reading the default image so that
                // fileForceContent work
                $default = public_path() . '/assets/profile-images/default-image';
                $handle = fopen($default, "rb");
                $image = fread($handle, filesize($default));
                fclose($handle);
                $path = public_path() . '/assets/profile-images/' . parse_url($home)['host'] . '/image';
                $this->fileForceContents($path, $image);
            }
            return true;
        }
        return false;
    }

    /**
     * A wrapper function for php-mf2’s parse method
     *
     * @param  string  The HTML to parse
     * @param  string  The base URL to resolve relative URLs in the HTML against
     * @return array   The porcessed microformats
     */
    private function parseHTML($html, $baseurl)
    {
        $microformats = \Mf2\parse((string) $html, $baseurl);

        return $microformats;
    }

    /**
     * Save a file, and create any necessary folders
     *
     * @param string  The directory to save to
     * @param binary  The file to save
     */
    private function fileForceContents($dir, $contents)
    {
        $parts = explode('/', $dir);
        $file = array_pop($parts);
        $dir = '';
        foreach ($parts as $part) {
            if (!is_dir($dir .= "/$part")) {
                mkdir($dir);
            }
        }
        file_put_contents("$dir/$file", $contents);
    }

    /**
     * Purify HTML received from a webmention
     *
     * @param  string  The HTML to be processed
     * @return string  The processed HTML
     */
    public function filterHTML($html)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path() . '/HTMLPurifier');
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }

    /**
     * Discover if a URL has a webmention endpoint
     *
     * @param  string  The URL
     * @return string  The webmention endpoint URL
     */
    private function discoverWebmentionEndpoint($url)
    {
        $endpoint = null;
        $client = new Client();

        try {
            $response = $client->get($url);
            //check HTTP Headers for webmention endpoint
            $links = \GuzzleHttp\Psr7\parse_header($response->getHeader('Link'));
            foreach ($links as $link) {
                if ($link['rel'] == 'webmention') {
                    return trim($link[0], '<>');
                }
            }

            //failed to find a header so parse HTML
            $html = (string) $response->getBody();

            $mf2 = new \Mf2\Parser($html, $url);
            $rels = $mf2->parseRelsAndAlternates();
            if (array_key_exists('webmention', $rels[0])) {
                $endpoint = $rels[0]['webmention'][0];
            } elseif (array_key_exists('http://webmention.org/', $rels[0])) {
                $endpoint = $rels[0]['http://webmention.org/'][0];
            }
            if ($endpoint) {
                if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
                    return $endpoint;
                }
                //it must be a relative url, so resolve with php-mf2
                $resolved = $mf2->resolveUrl($endpoint);
                return $resolved;
            }
            return false;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            return false;
        }
    }

    /**
     * Send the webmention to the given target
     *
     * @param  string  The endpoint to send to
     * @param  string  The source URL
     * @param  string  The target URL
     * @return bool
     */
    private function sendWebmention($endpoint, $source, $target)
    {
        $client = new Client();

        try {
            $client->post($endpoint, [
                'form_params' => [
                    'source' => $source,
                    'target' => $target
                ]
            ]);
            return true;
        } catch (GuzzleHttp\Exception\RequestException $e) {
            return false;
        }
    }
}
