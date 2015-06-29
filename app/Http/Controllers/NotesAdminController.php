<?php

namespace App\Http\Controllers;

use Twitter;
use App\Tag;
use App\Note;
use Imagine\Image\Box;
use Imagine\Gd\Imagine;
use Jonnybarnes\Posse\URL;
use Illuminate\Http\Request;
use Jonnybarnes\Posse\NotePrep;
use Imagine\Image\ImageInterface;
use App\Http\Controllers\Controller;

class NotesAdminController extends Controller
{
    public function __construct()
    {
        $this->imageResizeLimit = 800;
    }

    /**
     * Show the form to make a new note
     *
     * @return \Illuminate\View\Factory view
     */
    public function newNote()
    {
        return view('admin.newnote');
    }

    /**
     * List the notes that can be edited
     *
     * @return \Illuminate\View\Factory view
     */
    public function listNotes()
    {
        $notes = Note::select('id', 'note')->where('deleted', '0')->orderBy('id', 'desc')->get();
        return view('admin.listnotes', array('notes' => $notes));
    }

    /**
     * Display the form to edit a specific note
     *
     * @param  string The note id
     * @return \Illuminate\View\Factory view
     */
    public function editNote($noteId)
    {
        $note = Note::find($noteId);
        return view('admin.editnote', array('id' => $noteId, 'note' => $note));
    }

    /**
     * Process a request to make a new note
     *
     * @param Illuminate\Http\Request $request
     * @param string Set by an micropub API call
     * @param string The client id that made the API call
     * @todo  Sort this mess out
     */
    public function postNewNote(Request $request, $api = false, $client_id = null)
    {
        $noteprep = new NotePrep();
        $noteOrig = $request->input('content');
        $noteNfc = \Patchwork\Utf8::filter($noteOrig);

        $inputReplyTo = $request->input('in-reply-to');
        if ($inputReplyTo) {
            if ($inputReplyTo == '') {
                $replyTo = null;
            } else {
                $replyTo = $inputReplyTo;
            }
        } else {
            $replyTo = null;
        }
        //so now $replyTo is `null` or has a non-empty value

        if ($request->input('confirmlocation')) {
            if ($request->input('location')) {
                $formLocation = $request->input('location');
                if ($request->input('address')) {
                    $locadd = $formLocation . ':' . $request->input('address');
                } else {
                    $locadd = $formLocation;
                }
            }
        } else {
            $locadd = null;
        }

        $time = time();

        if ($request->hasFile('photo')) {
            $hasPhoto = true;
        } else {
            $hasPhoto = false;
        }

        try {
            $id = Note::insertGetId(
                array(
                    'note' => $noteNfc,
                    'timestamp' => $time,
                    'reply_to' => $replyTo,
                    'location' => $locadd,
                    'client_id' => $client_id,
                    'photo' => $hasPhoto
                )
            );
        } catch (\Exception $e) {
            $msg = $e->getMessage(); //do something
            return 'Error saving note' . $msg;
        }

        $url = new URL();
        $realid = $url->numto60($id);
        if ($request->hasFile('photo')) {
            $photoFilename = 'note-' . $realid;
            $photo = true;
            $path = public_path() . '/assets/img/notes/';
            $ext = $request->file('photo')->getClientOriginalExtension();
            $photoFilename .= '.' . $ext;
            $request->file('photo')->move($path, $photoFilename);
        } else {
            $photo = false;
        }

        $tags = $noteprep->getTags($noteNfc);
        $tagsToSave = [];
        foreach ($tags as $tag) {
            $tag_search = Tag::where('tag', $tag)->get();
            if (count($tag_search) == 0) {
                $newtag = new Tag;
                $newtag->tag = $tag;
                $newtag->save();
                $tag_id = $newtag->id;
            } else {
                $tag_id = $tag_search[0]->id;
            }
            $tagsToSave[$tag_id] = $tag;
        }
        $note = Note::find($id);
        foreach ($tagsToSave as $tag_id => $tag) {
            $note->tags()->attach($tag_id);
        }
        $longurl = 'https://' . config('url.longurl') . '/notes/' . $realid;
        $webmentions = null; //initialise variable
        if ($replyTo !== null) {
            //now we check if webmentions should be sent
            if ($request->input('webmentions')) {
                $wmc = new WebMentionsController();
                $webmentions = $wmc->send($replyTo, $longurl);
            }
        }

        $shorturlId = 't/' . $url->numto60($id);
        $shorturlBase = config('url.shorturl');
        $shorturl = 'https://' . $shorturlBase . '/' . $shorturlId;
        $noteNfcSwappedNames = $this->swapNames($noteNfc);
        if (
            (is_array($request->input('mp-syndicate-to')) && in_array('twitter.com/jonnybarnes', $request->input('mp-syndicate-to')))
            ||
            ($request->input('twitter') == true)
        ) {
            $tweet = $noteprep->createNote($noteNfcSwappedNames, $shorturlBase, $shorturlId, 140, true, true);
            $tweet_opts = array('status' => $tweet, 'format' => 'json');
            if ($replyTo) {
                $tweet_opts['in_reply_to_status_id'] = $noteprep->replyTweetId($replyTo);
            }
            if ($locadd) {
                $explode = explode(':', $locadd);
                if (count($explode) == 2) {
                    $location = explode(',', $explode[0]);
                } else {
                    $location = explode(',', $explode);
                }
                $lat = trim($location[0]);
                $long = trim($location[1]);
                $jsonPlaceId = Twitter::getGeoReverse(array('lat' => $lat, 'long' => $long, 'format' => 'json'));
                $parsePlaceId = json_decode($jsonPlaceId);
                $place_id = $parsePlaceId->result->places[0]->id ?: null;
                $tweet_opts['lat'] = $lat;
                $tweet_opts['long'] = $long;
                if ($place_id) {
                    $tweet_opts['place_id'] = $place_id;
                }
            }
            if ($photo) {
                $filenameParts = explode('.', $photoFilename);
                $preExt = count($filenameParts) - 2;
                $filenameParts[$preExt] .= '-small';
                $photoFilenameSmall = implode('.', $filenameParts);
                $imagine = new Imagine();
                $orig = $imagine->open(public_path() . '/assets/img/notes/' . $photoFilename);
                $size = array($orig->getSize()->getWidth(), $orig->getSize()->getHeight());
                if ($size[0] > $this->imageResizeLimit || $size[1] > $this->imageResizeLimit) {
                    $ar = $size[0]/$size[1];
                    if ($ar >= 1) {
                        //width > height
                        $newHeight = (int)round($this->imageResizeLimit/$ar);
                        $box = array($this->imageResizeLimit, $newHeight);
                    } else {
                        //height > width
                        $newWidth = (int)round($this->imageResizeLimit * $ar);
                        $box = array($newWidth, $this->imageResizeLimit);
                    }
                    $orig->resize(new Box($box[0], $box[1]))
                         ->save(public_path() . '/assets/img/notes/' . $photoFilenameSmall);
                    $tweet_opts["media[]"] = file_get_contents(public_path() . '/assets/img/notes/' . $photoFilenameSmall);
                } else {
                    $tweet_opts["media[]"] = file_get_contents(public_path() . '/assets/img/notes/' . $photoFilename);
                }
            }
            try {
                if ($photo) {
                    $response_json = Twitter::postTweetMedia($tweet_opts);
                } else {
                    $response_json = Twitter::postTweet($tweet_opts);
                }
                $response = json_decode($response_json);
                $tweet_id = $response->id;
                Note::find($id)->update(array('tweet_id' => $tweet_id));
            } catch (\Exception $e) {
                $tweet = 'Error sending tweet. <pre>' . $response_json . '</pre>';
            }
        } else {
            $tweet = null;
        }

        if ($api) {
            return $longurl;
        }
        return view('admin.newnotesuccess', array('id' => $id, 'shorturl' => $shorturl, 'tweet' => $tweet, 'webmentions' => $webmentions));
    }

    /**
     * Process a request to edit a note. Easy since this can only be done
     * from the admin CP.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postEditNote($noteId, Request $request)
    {
        $replyTo = $request->input('reply-to');
        $noteText = $request->input('note');
        $webmentions = $request->input('webmentions');

        //update note data
        $note = Note::find($noteId);
        $note->note = $noteText;
        $note->reply_to = $replyTo;
        $note->save();

        //send webmentions
        $webmentionsSent = null;
        if (($webmentions == true)  && ($replyTo != '')) {
            $longurl = 'https://' . config('url.longurl') . '/note/' . $noteId;
            $wmc = new WebMentionsController();
            $webmentionsSent = $wmc->send($replyTo, $longurl);
        }

        return view('admin.editnotesuccess', array('id' => $noteId, 'webmentions' => $webmentionsSent));
    }

    /**
     * Swap @names in a note
     *
     * When a note is being saved and we are posting it to twitter, we want
     * to swap our @local_name to Twitter’s @twitter_name so the user get’s
     * mentioned on Twitter.
     *
     * @param  string  The note to process
     * @return string  The processed note
     */
    public function swapNames($note)
    {
        $regex = '/\[.*?\](*SKIP)(*F)|@(\w+)/'; //match @alice but not [@bob](...)
        $tweet = preg_replace_callback(
            $regex,
            function ($matches) {
                try {
                    $contact = Contact::where('nick', '=', mb_strtolower($matches[1]))->firstOrFail();
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    return '@' . $matches[1];
                }
                $twitterHandle = $contact->twitter;
                return '@' . $twitterHandle;
            },
            $note
        );
        return $tweet;
    }
}
