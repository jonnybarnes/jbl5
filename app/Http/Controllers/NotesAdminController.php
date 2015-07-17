<?php

namespace App\Http\Controllers;

use App\Tag;
use App\Note;
use Normalizer;
use Jonnybarnes\Posse\URL;
use Illuminate\Http\Request;
use Jonnybarnes\Posse\NotePrep;
use App\Http\Controllers\Controller;

class NotesAdminController extends Controller
{
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
     * @param string The client id that made the API call
     * @todo  Sort this mess out
     */
    public function postNewNote(Request $request, $clientId = null)
    {
        $url = new URL();
        $noteprep = new NotePrep();

        $noteNfc = normalizer_normalize($request->input('content'), Normalizer::FORM_C);
        $location = $this->getLocation($request);

        try {
            $note = Note::create(
                array(
                    'note' => $noteNfc,
                    'in_reply_to' => $request->input('in-reply-to'),
                    'location' => $location,
                    'client_id' => $clientId,
                    'photo' => $request->hasFile('photo')
                )
            );
        } catch (\Exception $e) {
            $msg = $e->getMessage(); //do something
            return 'Error saving note' . $msg;
        }

        $realId = $url->numto60($note->id);

        $photosController = new PhotosController();
        $photosController->saveImage($request, $realId);

        $tags = $noteprep->getTags($noteNfc);
        $tagsToSave = [];
        foreach ($tags as $text) {
            $tag = Tag::firstOrCreate(['tag', $text]);
            $tagsToSave[] = $tag->id;
        }
        $note->tags()->attach($tagsToSave);

        $longurl = 'https://' . config('url.longurl') . '/notes/' . $realId;

        $wmc = new WebMentionsController();
        $webmentions = $wmc->send($request, $longurl);

        $shorturl = 'https://' . config('url.shorturl') . '/t/' . $url->numto60($note->id);

        if (
            (is_array($request->input('mp-syndicate-to')) && in_array('twitter.com/jonnybarnes', $request->input('mp-syndicate-to')))
            ||
            ($request->input('mp-syndicate-to') == 'twitter.com/jonnybarnes')
            ||
            ($request->input('twitter') == true)
        ) {
            $this->dispatch(new SyndicateToTwitter($note));
        }

        if ($clientId) {
            return $longurl;
        }
        return view('admin.newnotesuccess', array('id' => $note->id, 'shorturl' => $shorturl, 'webmentions' => $webmentions));
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
        //update note data
        $note = Note::find($noteId);
        $note->note = $request->input('note');
        $note->reply_to = $request->input('reply-to');
        $note->save();

        //send webmentions
        $webmentionsSent = null;
        if (($request->input('webmentions') == true)  && ($request->input('reply-to') != '')) {
            $longurl = 'https://' . config('url.longurl') . '/note/' . $noteId;
            $wmc = new WebMentionsController();
            $webmentionsSent = $wmc->send($request->input('reply-to'), $longurl);
        }

        return view('admin.editnotesuccess', array('id' => $noteId, 'webmentions' => $webmentionsSent));
    }

    /**
     * Get the relavent location information from the input
     *
     * @param  \Illuminate\Http\Request $request
     * @return string | null
     */
    private function getLocation(Request $request)
    {
        if ($request->input('confirmlocation')) {
            if ($request->input('location')) {
                $location = $request->input('location');
                if ($request->input('address')) {
                    $location .= ':' . $request->input('address');
                }
                return $location;
            }
            return null;
        }
        return null;
    }
}
