<?php

namespace App\Http\Controllers;

use App\Tag;
use App\Note;
use Illuminate\Http\Request;
use App\Jobs\SyndicateToTwitter;
use Jonnybarnes\IndieWeb\Numbers;
use Jonnybarnes\IndieWeb\NotePrep;

class NotesAdminController extends Controller
{
    /**
     * Show the form to make a new note.
     *
     * @return \Illuminate\View\Factory view
     */
    public function newNote()
    {
        return view('admin.newnote');
    }

    /**
     * List the notes that can be edited.
     *
     * @return \Illuminate\View\Factory view
     */
    public function listNotes()
    {
        $notes = Note::select('id', 'note')->orderBy('id', 'desc')->get();
        foreach ($notes as $note) {
            $note->originalNote = $note->getOriginal('note');
        }

        return view('admin.listnotes', ['notes' => $notes]);
    }

    /**
     * Display the form to edit a specific note.
     *
     * @param  string The note id
     * @return \Illuminate\View\Factory view
     */
    public function editNote($noteId)
    {
        $note = Note::find($noteId);
        $note->originalNote = $note->getOriginal('note');

        return view('admin.editnote', ['id' => $noteId, 'note' => $note]);
    }

    /**
     * Process a request to make a new note.
     *
     * @param Illuminate\Http\Request $request
     * @param string The client id that made the API call
     * @todo  Sort this mess out
     */
    public function postNewNote(Request $request, $clientId = null)
    {
        $numbers = new Numbers();
        $noteprep = new NotePrep();

        $location = $this->getLocation($request);

        try {
            $note = Note::create(
                [
                    'note' => $request->input('content'),
                    'in_reply_to' => $request->input('in-reply-to'),
                    'location' => $location,
                    'client_id' => $clientId,
                ]
            );
        } catch (\Exception $e) {
            $msg = $e->getMessage(); //do something

            return 'Error saving note' . $msg;
        }

        $realId = $numbers->numto60($note->id);

        //add images to media library
        $note->addMedia($request->file('photo'))->toMediaLibrary();

        $tags = $noteprep->getTags($request->input('content'));
        $tagsToSave = [];
        foreach ($tags as $text) {
            $tag = Tag::firstOrCreate(['tag', $text]);
            $tagsToSave[] = $tag->id;
        }
        $note->tags()->attach($tagsToSave);

        $longurl = 'https://' . config('url.longurl') . '/notes/' . $realId;

        if ($request->input('webmentions')) {
            $wmc = new WebMentionsController();
            $wmc->send($note, $longurl);
        }

        $shorturl = 'https://' . config('url.shorturl') . '/t/' . $numbers->numto60($note->id);

        if ((is_array($request->input('mp-syndicate-to'))
                &&
            in_array('twitter.com/jonnybarnes', $request->input('mp-syndicate-to')))
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

        return view('admin.newnotesuccess', ['id' => $note->id, 'shorturl' => $shorturl]);
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
        $note->note = $request->input('content');
        $note->in_reply_to = $request->input('in-reply-to');
        $note->save();

        if ($request->input('webmentions')) {
            $longurl = 'https://' . config('url.longurl') . '/note/' . $noteId;
            $wmc = new WebMentionsController();
            $wmc->send($note, $longurl);
        }

        return view('admin.editnotesuccess', ['id' => $noteId]);
    }

    /**
     * Get the relavent location information from the input.
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

            return;
        }

        return;
    }
}
