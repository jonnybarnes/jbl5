<?php

namespace App\Http\Controllers;

use Cache;
use Twitter;
use App\Tag;
use App\Note;
use App\Client;
use App\Contact;
use Carbon\Carbon;
use Jonnybarnes\IndieWeb\Numbers;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// Need to sort out Twitter and webmentions!

class NotesController extends Controller
{
    /**
     * Show all the notes.
     *
     * @return \Illuminte\View\Factory view
     */
    public function showNotes()
    {
        $numbers = new Numbers();
        $notes = Note::orderBy('updated_at', 'desc')->with('webmentions')->simplePaginate(10);
        foreach ($notes as $note) {
            $note->nb60id = $numbers->numto60($note->id);
            $replies = 0;
            foreach ($note->webmentions as $webmention) {
                if ($webmention->type == 'reply') {
                    $replies = $replies + 1;
                }
            }
            $note->replies = $replies;
            $note->twitter = $this->checkTwitterReply($note->in_reply_to);
            $note->note = $this->autoLinkHashtag($this->makeHCards($note->note));
            $note->iso8601_time = $note->updated_at->toISO8601String();
            $note->human_time = $note->updated_at->diffForHumans();
            if ($note->location) {
                $pieces = explode(':', $note->location);
                $latlng = explode(',', $pieces[0]);
                $note->latitude = trim($latlng[0]);
                $note->longitude = trim($latlng[1]);
                if (count($pieces) == 2) {
                    $note->address = $pieces[1];
                }
            }
            if ($note->photo == true) {
                $photosController = new PhotosController();
                $note->photopath = $photosController->getPhotoPath($note->nb60id);
            }
        }

        return view('allnotes', ['notes' => $notes]);
    }

    /**
     * Show a single note.
     *
     * @param  string The id of the note
     * @param  \App\Client $client
     * @return \Illuminate\View\Factory view
     */
    public function singleNote($urlId, Client $client)
    {
        $numbers = new Numbers();
        $realId = $numbers->b60tonum($urlId);
        $carbon = new Carbon();
        $note = Note::find($realId);
        $note->nb60id = $urlId;
        if ($note->client_id) {
            $note->client_name = $client->getClientName($note->client_id);
        }
        $replies = [];
        $reposts = [];
        $likes = [];
        foreach ($note->webmentions as $webmention) {
            switch ($webmention->type) {
                case 'reply':
                    $content = unserialize($webmention->content);
                    $content['source'] = $this->bridgyReply($webmention->source);
                    $content['photo'] = $this->createPhotoLink($content['photo']);
                    $content['date'] = $carbon->parse($content['date'])->toDayDateTimeString();
                    $replies[] = $content;
                    break;

                case 'repost':
                    $content = unserialize($webmention->content);
                    $content['photo'] = $this->createPhotoLink($content['photo']);
                    $content['date'] = $carbon->parse($content['date'])->toDayDateTimeString();
                    $reposts[] = $content;
                    break;

                case 'like':
                    $content = unserialize($webmention->content);
                    $content['photo'] = $this->createPhotoLink($content['photo']);
                    $likes[] = $content;
                    break;
            }
        }
        $note->twitter = $this->checkTwitterReply($note->in_reply_to);
        $note->note = $this->autoLinkHashtag($this->makeHCards($note->note));
        $note->iso8601_time = $note->updated_at->toISO8601String();
        $note->human_time = $note->updated_at->diffForHumans();
        if ($note->location) {
            $pieces = explode(':', $note->location);
            $latlng = explode(',', $pieces[0]);
            $note->latitude = trim($latlng[0]);
            $note->longitude = trim($latlng[1]);
            if (count($pieces) == 2) {
                $note->address = $pieces[1];
            }
        }
        if ($note->photo == true) {
            $photosController = new PhotosController();
            $note->photopath = $photosController->getPhotoPath($note->nb60id);
        }

        return view('singlenote', [
            'note' => $note,
            'replies' => $replies,
            'reposts' => $reposts,
            'likes' => $likes,
        ]);
    }

    /**
     * Redirect /note/{decID} to /notes/{nb60id}.
     *
     * @param  string The decimal id of he note
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function singleNoteRedirect($decId)
    {
        $numbers = new Numbers();
        $realId = $numbers->numto60($decId);

        $url = 'https://' . config('url.longurl') . '/notes/' . $realId;

        return redirect($url);
    }

    /**
     * Show all notes tagged with {tag}.
     *
     * @param  string The tag
     * @return \Illuminate\View\Factory view
     */
    public function taggedNotes($tag)
    {
        $tagId = Tag::where('tag', $tag)->pluck('id');
        $notes = Tag::find($tagId)->notes()->orderBy('updated_at', 'desc')->get();
        foreach ($notes as $note) {
            $note->note = $this->TransformNote($note->note);
            $note->iso8601_time = $note->updated_at->toISO8601String();
            $note->human_time = $note->updated_at->diffForHumans();
        }

        return view('taggednotes', ['notes' => $notes, 'tag' => $tag]);
    }

    /**
     * Note that this method does two things, given @username (NOT [@username](URL)!)
     * we try to create a fancy hcard from our contact info. If this is not possible
     * due to lack of contact info, we assume @username is a twitter handle and link it
     * as such.
     *
     * @param  string  The note’s text
     * @return string
     */
    public function makeHCards($text)
    {
        $regex = '/\[.*?\](*SKIP)(*F)|@(\w+)/'; //match @alice but not [@bob](...)
        $hcards = preg_replace_callback(
            $regex,
            function ($matches) {
                try {
                    $contact = Contact::where('nick', '=', mb_strtolower($matches[1]))->firstOrFail();
                } catch (ModelNotFoundException $e) {
                    return '<a href="https://twitter.com/' . $matches[1] . '">' . $matches[0] . '</a>';
                }
                $path = parse_url($contact->homepage)['host'];
                $contact->photo = (file_exists(public_path() . '/assets/profile-images/' . $path . '/image')) ?
                    '/assets/profile-images/' . $path . '/image'
                :
                    '/assets/profile-images/default-image';

                return view('mini-hcard-template', ['contact' => $contact])->render();
            },
            $text
        );

        return $hcards;
    }

    /**
     * Given a string and section, finds all hashtags matching
     * `#[\-_a-zA-Z0-9]+` and wraps them in an `a` element with
     * `rel=tag` set and a `href` of 'section/tagged/' + tagname without the #.
     *
     * @param  string  The note
     * @param  string  The section (such as blog)
     * @return string
     */
    public function autoLinkHashtag($text, $section = 'notes')
    {
        // $replacements = ["#tag" => "<a rel="tag" href="/tags/tag">#tag</a>]
        $replacements = [];
        $matches = [];

        if (preg_match_all('/(?<=^|\s)\#([a-zA-Z0-9\-\_]+)/i', $text, $matches, PREG_PATTERN_ORDER)) {
            // Look up #tags, get Full name and URL
            foreach ($matches[0] as $name) {
                $name = str_replace('#', '', $name);
                $replacements[$name] =
                  '<a rel="tag" class="p-category" href="/' . $section . '/tagged/' . $name . '">#' . $name . '</a>';
            }

            // Replace #tags with valid microformat-enabled link
            foreach ($replacements as $name => $replacement) {
                $text = str_replace('#' . $name, $replacement, $text);
            }
        }

        return $text;
    }

    /**
     * Swap a brid.gy URL shim-ing a twitter reply to a real twitter link.
     *
     * @param  string
     * @return string
     */
    public function bridgyReply($source)
    {
        $url = $source;
        if (mb_substr($source, 0, 28, 'UTF-8') == 'https://brid-gy.appspot.com/') {
            $parts = explode('/', $source);
            $tweetId = array_pop($parts);
            if ($tweetId) {
                $url = 'https://twitter.com/_/status/' . $tweetId;
            }
        }

        return $url;
    }

    /**
     * Create the photo link.
     *
     * @param  string
     * @return string
     */
    public function createPhotoLink($url)
    {
        $host = parse_url($url)['host'];
        if ($host != 'twitter.com' && $host != 'pbs.twimg.com') {
            return '/assets/profile-images/' . $host . '/image';
        }
        if (mb_substr($url, 0, 20) == 'http://pbs.twimg.com') {
            return str_replace('http://', 'https://', $url);
        }

        return;
    }

    /**
     * Twitter!!!
     *
     * @param  string  The reply to URL
     * @return string | null
     */
    private function checkTwitterReply($url)
    {
        if ($url == null) {
            return;
        }

        if (mb_substr($url, 0, 20, 'UTF-8') !== 'https://twitter.com/') {
            return;
        }

        $arr = explode('/', $url);
        $tweetId = end($arr);
        if (Cache::has($tweetId)) {
            return Cache::get($tweetId);
        }
        try {
            $oEmbed = Twitter::getOembed([
                'id' => $tweetId,
                'align' => 'center',
                'omit_script' => true,
                'maxwidth' => 550,
            ]);
        } catch (\Exception $e) {
            return;
        }
        Cache::put($tweetId, $oEmbed, ($oEmbed->cache_age / 60));

        return $oEmbed;
    }
}
