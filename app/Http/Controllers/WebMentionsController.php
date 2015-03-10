<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Note;
use App\WebMention;
use GuzzleHttp\Client;
use Mf2\parse;
use Chromabits\Purifier\Contracts\Purifier;
use HTMLPurifier_Config;
use Jonnybarnes\Posse\URL;
use Jonnybarnes\WebmentionsParser\Parser;
use App\Esceptions\RemoteContentNotFound;
use App\Exceptions\ParsingException;

class WebMentionsController extends Controller
{
    /**
     * @var Purifier
     */
    protected $purifier;

    /*
     * Receive and process a webmention
     */
    public function receive(Request $request)
    {
        $target = $request->input('target');
        $source = $request->input('source');

        //first we trivually reject requets that lack all required inputs
        if (!($target) || !($source)) {
            return (new Response(
                'You need both the target and source parameters',
                400
            ));
        }

        //next check the $target is valid
        $sourceurl = parse_url($source); //what are these for?
        $baseurl = $sourceurl['scheme'] . '://' . $sourceurl['host'];
        $path = parse_url($target)['path'];
        $pathParts = explode('/', $path);

        switch ($pathParts[1]) {
            case 'notes':
                //we have anote
                $noteId = $pathParts[2];
                $url = new URL();
                $realId = $url->b60tonum($noteId);
                try {
                    $note = Note::findOrFail($realId);
                    $parser = new Parser();
                    try {
                        $remoteContent = $this->getRemoteContent($source);
                        $mf = $this->parsHTML($remoteContent);
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
                                            if ($parser->checkInReplyTo($mf, $target) == false) {
                                                //it doesn't so delete
                                                $webmention->delete();
                                                return (new Response('The webmention has been deleted', 202));
                                            } else {
                                                //webmenion is still a reply, so update content
                                                try {
                                                    $content = $this->replyContent($mf);
                                                    $webmention->content = $content;
                                                    $webmention->save();
                                                    return (new Response('The webmention has been updated', 202));
                                                } catch (Exception $e) {
                                                    return (new Response('There was an error parsing the content from your site', 400));
                                                }
                                            }
                                            break;
                                        case 'like':
                                            if ($parser->checkLikeOf($mf, $target) == false) {
                                                //it doesn't so delete
                                                $webmention->delete();
                                                return Response::make('The webmention has been deleted', 202);
                                            } //note we don't need to do anything if it still is a like
                                            break;
                                        case 'repost':
                                            if ($parser->checkRepostOf($mf, $target) == false) {
                                                //it doesn't so delete
                                                $webmention->delete();
                                                return Response::make('The webmention has been deleted', 202);
                                            } //again, we don't need to do anything if it still is a repost
                                            break;
                                    }
                                }
                            }
                        } else {
                            //no wemention in db so create new one
                            $webmention = new WebMention();
                            //check it is in fact a reply
                            if ($parser->checkInReplyTo($mf, $target)) {
                                try {
                                    $content = $parser->replyContent($mf);
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
                            } elseif ($parser->checkLikeOf($mf, $target)) {
                                //it is a like
                                try {
                                    $content = $parser->likeContent($mf);
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
                            } elseif ($parser->checkRepostOf($mf, $target)) {
                                //it is a repost
                                try {
                                    $content = $parser->repostContent($mf);
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
                            } else {
                                return (new Response('Your webmention does not actually link to my note', 400));
                            }
                        }
                    } catch (RemoteContentNotFound $e) {
                        return (new Response(
                            'Error retreiving the webmention',
                            400
                        ));
                    }

                } catch (ModelNotFoundException $e) {
                    return (new Response(
                        'This note doesn’t exist.',
                        400
                    ));
                }
                break;
            case 'blog':
                return (new Response(
                    'I don’t accept webmentions for blog posts yet.',
                    400
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

    public function getRemoteContent($url)
    {
        $client = new Client();

        try {
            $response = $client->get($url);
            $html = $response->getBody(true);
            $path = storage_path() . '/HTML/' . $this->URLtoFileName($url);
            $this->fileForceContents($path, $html);

            return $html;
        } catch (GuzzleHttp\Exception\RequestException $e) {
            throw new RemoteContentNotFound;
        }
    }

    public function saveImage($content)
    {
        $photo = $content['photo'];
        $home = $content['url'];
        //dont save pbs.twimg.com links
        if (parse_url($photo)['host'] != 'pbs.twimg.com') {
            $client = new Client();
            try {
                $response = $client->get($photo);
                $image = $response->getBody(true);
                $path = public_path() . '/assets/profile-images/' . parse_url($home)['host'] . '/image';
                $this->fileForceContents($path, $image);
            } catch (Exception $e) {
                $default = public_path() . '/assets/profile-images/default-image';
                $handle = fopen($default, "rb");
                $image = fread($handle, filesize($default));
                fclose($handle);
                $path = public_path() . '/assets/profile-images/' . parse_url($home)['host'] . '/image';
                $this->fileForceContents($path, $image);
            }
            return true;
        } else {
            return false;
        }
    }

    public function parseHTML($html, $baseurl)
    {
        $mf = \Mf2\parse((string) $html, $baseurl);

        return $mf;
    }

    public function fileForceContents($dir, $contents)
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

    public function filterHTML($html, Purifier $purifier)
    {
        $this->purifier = $purifier;
        $htmlClean = $this->purifier->clean($html);

        return $htmlClean;
    }
}
