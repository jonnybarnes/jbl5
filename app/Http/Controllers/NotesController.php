<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Note;
use App\Tag;
use App\Contact;
use DB;
use Twitter;
use Carbon\Carbon;
use League\CommonMark\CommonMarkConverter;
use Jonnybarnes\Posse\NotePrep;
use Jonnybarnes\Posse\URL;
use Jonnybarnes\UnicodeTools\UnicodeTools;

// Need to sort out Twitter and webmentions!

class NotesController extends Controller
{
    /**
     *
     * Show all notes
     */
    public function showNotes()
    {
        $carbon = new Carbon();
        $noteprep = new NotePrep();
        $notes = Note::where('deleted', '0')->orderBy('timestamp', 'desc')->simplePaginate(10);
        foreach ($notes as $note) {
            $url = new URL();
            $note['nb60id'] = $url->numto60($note['id']);
            $replies = 0;
            foreach ($note->webmentions as $webmention) {
                if ($webmention->type == 'reply') {
                    $replies = $replies + 1;
                }
            }
            $note['replies'] = $replies;
            $note['note'] = $this->transformNote($note['note']);
            $note['iso8601_time'] = $carbon->createFromTimeStamp($note['timestamp'])->toISO8601String();
            $note['human_time'] = $carbon->createFromTimeStamp($note['timestamp'])->diffForHumans();
            if ($note['reply_to']) {
                if (mb_substr($note['reply_to'], 0, 19, "UTF-8") == 'https://twitter.com') {
                    $id = $noteprep->replyTweetId($note['reply_to']);
                    $tweet = Twitter::getTweet($id);
                    $note['reply_to_url'] = $note['reply_to'];
                    $note['reply_to_text'] = Twitter::linkify($tweet->text);
                    $note['reply_to_author_name'] = $tweet->user->name;
                    $note['reply_to_screen_name'] = $tweet->user->screen_name;
                    $note['reply_to_profile_photo'] = $this->secureTwimgLink($tweet->user->profile_image_url);

                }
            }
            if ($note['location']) {
                $pieces = explode(':', $note['location']);
                $latlng = explode(',', $pieces[0]);
                $note['latitude'] = trim($latlng[0]);
                $note['longitude'] = trim($latlng[1]);
                if (count($pieces) == 2) {
                    $note['address'] = $pieces[1];
                }
            }
            if ($note['photo'] == true) {
                $fs = new Filesystem();
                $photoDir = public_path() . '/assets/img/notes';
                $idname = 'note-' . $note['nb60id'];
                $files = $fs->files($photoDir);
                foreach ($files as $file) {
                    $parts = explode('.', $file);
                    $name = $parts[0];
                    $dirs = explode('/', $name);
                    $actualname = last($dirs);
                    if ($actualname == $idname) {
                        $ext = $parts[1];
                    }
                }
                if (isset($ext)) {
                    $note['photopath'] = '/assets/img/notes/' . $idname . '.' . $ext;
                }
            }
        }
        return view('allnotes', array('notes' => $notes));
    }

    /**
     *
     * Show a single note
     */
    public function singleNote($id)
    {
        $url = new URL();
        $realid = $url->b60tonum($id);
        $carbon = new Carbon();
        $noteprep = new NotePrep();
        $note = Note::find($realid);
        $note->nb60id = $id;
        if ($note->client_id) {
            $client_name = DB::table('clients')->where('client_url', $note->client_id)->pluck('client_name');
            if ($client_name) {
                $note->client_name = $client_name;
            } else {
                $url = parse_url($note->client_id);
                $note->client_name = $url['host'];
                if (isset($url['path'])) {
                    $note->client_name .= $url['path'];
                }
            }
        }
        $replies = array();
        $reposts = array();
        $likes = array();
        foreach ($note->webmentions as $webmention) {
            if ($webmention->type == 'reply') {
                $content = unserialize($webmention->content);
                $content['source'] = $this->bridgyReply($webmention->source);
                $url = parse_url($content['photo'])['host'];
                if ($url != 'pbs.twimg.com' && $url != 'twitter.com') {
                    $content['photo'] = '/assets/profile-images/' . $url . '/image';
                }
                $content['photo'] = $this->secureTwimgLink($content['photo']);
                $content['date'] = $carbon->parse($content['date'])->toDayDateTimeString();
                $replies[] = $content;
            }
            if ($webmention->type == 'repost') {
                $content = unserialize($webmention->content);
                $url = parse_url($content['photo'])['host'];
                if ($url != 'twitter.com' && $url != 'pbs.twimg.com') {
                    $content['photo'] = '/assets/profile-images/' . $url . '/image';
                } else {
                    $content['photo'] = $this->secureTwimgLink($content['photo']);
                }
                $content['date'] = $carbon->parse($content['date'])->toDayDateTimeString();
                $reposts[] = $content;
            }
            if ($webmention->type == 'like') {
                $content = unserialize($webmention->content);
                $url = parse_url($content['photo'])['host'];
                if ($url != 'twitter.com' && $url != 'pbs.twimg.com') {
                    $content['photo'] = '/assets/profile-images/' . $url . '/image';
                } else {
                    $content['photo'] = $this->secureTwimgLink($content['photo']);
                }
                $likes[] = $content;
            }
        }
        $note->note = $this->transformNote($note->note);
        $note->iso8601_time = $carbon->createFromTimeStamp($note->timestamp)->toISO8601String();
        $note->human_time = $carbon->createFromTimeStamp($note->timestamp)->diffForHumans();
        if ($note->reply_to) {
            if (mb_substr($note->reply_to, 0, 19, "UTF-8") == 'https://twitter.com') {
                $id = $noteprep->replyTweetId($note->reply_to);
                $tweet = Twitter::getTweet($id);
                $note->reply_to_url = $note->reply_to;
                $note->reply_to_text = Twitter::linkify($tweet->text);
                $note->reply_to_author_name = $tweet->user->name;
                $note->reply_to_screen_name = $tweet->user->screen_name;
                $note->reply_to_profile_photo = $this->secureTwimgLink($tweet->user->profile_image_url);

            }
        }
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
            $fs = new Filesystem();
            $photoDir = public_path() . '/assets/img/notes';
            $idname = 'note-' . $id;
            $files = $fs->files($photoDir);
            foreach ($files as $file) {
                $parts = explode('.', $file);
                $name = $parts[0];
                $dirs = explode('/', $name);
                $actualname = last($dirs);
                if ($actualname == $idname) {
                    $ext = $parts[1];
                }
            }
            if (isset($ext)) {
                $note->photopath = '/assets/img/notes/' . $idname . '.' . $ext;
            }
        }

        return view('singlenote', array('note' => $note, 'replies' => $replies, 'reposts' => $reposts, 'likes' => $likes));
    }

    /**
     * Redirect /note/{decID} to /notes/{nb60id}
     *
     */
    public function singleNoteRedirect($id)
    {
        $url = new URL();
        $realid = $url->numto60($id);

        $url = 'https://' . config('url.longurl') . '/notes/' . $realid;

        return redirect($url);
    }

    /**
     * Show all notes tagged with {tag}
     *
     */
    public function taggedNotes($tag)
    {
        $carbon = new Carbon();
        $tagId = Tag::where('tag', $tag)->pluck('id');
        $notes = Tag::find($tagId)->notes()->orderBy('timestamp', 'desc')->get();
        foreach ($notes as $note) {
            $note['note'] = $this->TransformNote($note['note']);
            $note['iso8601_time'] = $carbon->createFromTimeStamp($note['timestamp'])->toISO8601String();
            $note['human_time'] = $carbon->createFromTimeStamp($note['timestamp'])->diffForHumans();
        }

        return view('taggednotes', array('notes' => $notes, 'tag' => $tag));
    }

    /**
     * Pre-process notes for web-view
     *
     * @return string
     */
    public function transformNote($text)
    {
        $unicode = new UnicodeTools();
        $codepoints = $unicode->convertUnicodeCodepoints($text);
        $markdown = new CommonMarkConverter();
        $transformed = $markdown->convertToHtml($codepoints);
        $hashtags = $this->autoLinkHashtag($transformed, 'notes');
        $names = $this->makeHCards($hashtags);
        $abbr = $this->addAbbrTag($names);

        return $abbr;
    }

    /**
     * Note that this method does two things, given @username (NOT [@username](URL)!)
     * we try to create a fancy hcard from our contact info. If this is not possible
     * due to lack of contact info, we assume @username is a twitter handle and link it
     * as such
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
                if (file_exists(public_path() . '/assets/profile-images/' . $path . '/image')) {
                    $contact->photo = '/assets/profile-images/' . $path . '/image';
                } else {
                    $contact->photo = $contact->photo = '/assets/profile-images/default-image';
                }
                return view('mini-hcard-template', array('contact' => $contact))->render();
            },
            $text
        );

        return $hcards;
    }

    /**
     * Given a string and section, finds all hashtags matching
     * `#[\-_a-zA-Z0-9]+` and wraps them in an `a` element with
     * `rel=tag` set and a `href` of 'section/tagged/' + tagname without the #.
     */
    public function autoLinkHashtag($text, $section)
    {
        // $replacements = ["#tag" => "<a rel="tag" href="/tags/tag">#tag</a>]
        $replacements = [];
        $matches = [];

        if (preg_match_all('/(?<=^|\s)\#([a-zA-Z0-9\-\_]+)/i', $text, $matches, PREG_PATTERN_ORDER)) {
            // Look up #tags, get Full name and URL
            foreach ($matches[0] as $name) {
                $name = str_replace('#', '', $name);
                $replacements[$name] = '<a rel="tag" class="p-category" href="/' . $section . '/tagged/' . $name . '">#' . $name . '</a>';
            }

            // Replace #tags with valid microformat-enabled link
            foreach ($replacements as $name => $replacement) {
                $text = str_replace('#' . $name, $replacement, $text);
            }
        }

        return $text;
    }

    /*
     * Add the <abbr> element to known abbr
     */
    public function addAbbrTag($text)
    {
        $abbreviations = [
            'HTML' => 'HyperText Markup Language',
            'XML' => 'eXtensible Markup Language',
            'TIL' => 'Today I Learned',
            'ICYMI' => 'In Case You Missed It',
            'FWIW' => 'For What It’s Worth'
        ];
        $regex = '/(?<!\#)\b(';
        $i = 0;
        $len = count($abbreviations);
        foreach ($abbreviations as $key => $value) {
            $regex .= $key;
            if ($i < ($len - 1)) {
                $regex .= '|';
            }
            $i++;
        }
        $regex .= ')\b/';
        $text = preg_replace_callback(
            $regex,
            function ($matches) use ($abbreviations) {
                if (array_key_exists($matches[0], $abbreviations)) {
                    return '<abbr title="' . $abbreviations[$matches[0]] . '">' . $matches[0] . '</abbr>';
                } else {
                    return $matches[0];
                }
            },
            $text
        );
        return $text;
    }

    public function bridgyReply($source)
    {
        $url = $source;
        if (mb_substr($source, 0, 28, "UTF-8") == 'https://brid-gy.appspot.com/') {
            $parts = explode('/', $source);
            $tweet_id = array_pop($parts);
            if ($tweet_id) {
                $url = 'https://twitter.com/_/status/' . $tweet_id;
            }
        }

        return $url;
    }

    public function secureTwimgLink($url)
    {
        if (mb_substr($url, 0, 20) == 'http://pbs.twimg.com') {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }
}
