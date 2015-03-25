<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Filesystem\Filesystem;
use App\Article;
use App\Note;
use App\Contact;
use App\Tag;
use DB;
use Twitter;
use Jonnybarnes\Posse\NotePrep;
use Jonnybarnes\Posse\URL;
use GuzzleHttp\Client;
use Mf2;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Gd\Imagine;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | Here we have the logic for the admin cp
    |
     */

    /**
     * Set variables
     *
     * @var string
     */
    public function __construct()
    {
        $this->imageResizeLimit = 800;
        $this->username = env('ADMIN_USER');
    }

    /**
     * Show the main admin CP page
     *
     * @return \Illuminate\View\Factory view
     */
    public function showWelcome()
    {
        return view('admin.welcome', array('name' => $this->username));
    }

    /**
     * Show the new article form
     *
     * @return \Illuminate\View\Factory view
     */
    public function newArticle()
    {
        $message = session('message');
        return view('admin.newarticle', array('message' => $message));
    }

    /**
     * List the articles that can be edited
     *
     * @return \Illuminate\View\Factory view
     */
    public function listArticles()
    {
        $posts = Article::select('id', 'title', 'published')->where('deleted', '0')->orderBy('id', 'desc')->get();
        return view('admin.listarticles', array('posts' => $posts));
    }

    /**
     * Show the edit form for an existing article
     *
     * @param  string  The article id
     * @return \Illuminate\View\Factory view
     */
    public function editArticle($id)
    {
        $post = Article::select(
            'title',
            'main',
            'url',
            'date_time',
            'published'
        )->where('id', $id)->get();
        return view('admin.editarticle', array('id' => $id, 'post' => $post));
    }

    /**
     * Show the delete confirmation form for an article
     *
     * @param  string  The article id
     * @return \Illuminate\View\Factory view
     */
    public function deleteArticle($id)
    {
        return view('admin.deletearticle', array('id' => $id));
    }

    /**
     * Process an incoming request for a new article and save it.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postNewArticle(Request $request)
    {
        $title = $request->input('title');
        $url = $request->input('url');
        $main = $request->input('main');
        $published = $request->input('published');
        if ($published == null) {
            $published = '0';
        }
        $time = time();

        try {
            $id = Article::insertGetId(
                array(
                    'url' => $url,
                    'title' => $title,
                    'main' => $main,
                    'date_time' => $time,
                    'published' => $published
                )
            );
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $unique = strpos($msg, '1062');
            if ($unique) {
                //We've checked for error 1062, i.e. duplicate titleurl
                return redirect('admin/blog/new')->withInput()->with('message', 'Duplicate title, please change');
            } else {
                //this isn't the error you're looking for
                throw $e;
            }
        }
        return view('admin.newarticlesuccess', array('id' => $id, 'title' => $title));
    }

    /**
     * Process an incoming request to edit an article
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate|View\Factory view
     */
    public function postEditArticle($id, Request $request)
    {
        $title = $request->input('title');
        $url = $request->input('url');
        $main = $request->input('main');
        $time = $request->input('time');
        $time = strtotime($time);
        $published = $request->input('published');

        $article = Article::find($id);
        $article->title = $title;
        $article->url = $url;
        $article->main = $main;
        $article->date_time = $time;
        $article->published = $published;
        $article->save();
        return view('admin.editarticlesuccess', array('id' => $id));
    }

    /**
     * Process a request to delete an aricle
     *
     * @param  string The article id
     * @return \Illuminate\View\Factory view
     */
    public function postDeleteArticle($id)
    {
        Article::where('id', $id)->update(array('deleted' => '1'));
        return view('admin.deletearticlesuccess', array('id' => $id));
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
    public function editNote($id)
    {
        $note = Note::find($id);
        return view('admin.editnote', array('id' => $id, 'note' => $note));
    }

    /**
     * Process a request to make a new note
     *
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
                $wm = new WebMentionsController();
                $webmentions = $wm->send($replyTo, $longurl);
            }
        }

        $shorturlId = 't/' . $url->numto60($id);
        $shorturlBase = config('url.shorturl');
        $shorturl = 'https://' . $shorturlBase . '/' . $shorturlId;
        $noteNfcNamesSwapped = $this->swapNames($noteNfc);
        if (in_array('twitter.com/jonnybarnes', $request->input('syndicate-to'))) {
            $tweet = $noteprep->createNote($noteNfcNamesSwapped, $shorturlBase, $shorturlId, 140, true, true);
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
            } catch (Exception $e) {
                $tweet = 'Error sending tweet. <pre>' . $response_json . '</pre>';
            }
        } else {
            $tweet = null;
        }

        if ($api) {
            return $longurl;
        } else {
            return view('admin.newnotesuccess', array('id' => $id, 'shorturl' => $shorturl, 'tweet' => $tweet, 'webmentions' => $webmentions));
        }
    }

    /**
     * Process a request to edit a note. Easy since this can only be done
     * from the admin CP.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postEditNote($id, Request $request)
    {
        $replyTo = $request->input('reply-to');
        $noteText = $request->input('note');
        $webmentions = $request->input('webmentions');

        //update note data
        $note = Note::find($id);
        $note->note = $noteText;
        $note->reply_to = $replyTo;
        $note->save();

        //send webmentions
        if (($webmentions == true)  && ($replyTo != '')) {
            $longurl = 'https://' . config('url.longurl') . '/note/' . $id;
            $wmc = new WebMentionsController();
            $webmentionsSent = $wmc->send($replyTo, $longurl);
        } else {
            $webmentionsSent = null;
        }

        return view('admin.editnotesuccess', array('id' => $id, 'webmentions' => $webmentionsSent));
    }

    /**
     * Show all the saved tokens
     *
     * @return \Illuminate\View\Factory view
     */
    public function showTokens()
    {
        $t = new TokensController();
        $tokens = $t->getAll();

        return view('admin.listtokens', array('tokens' => $tokens));
    }

    /**
     * Show the form to delete a certain token
     *
     * @param  string The token id
     * @return \Illuminate\View\Factory view
     */
    public function deleteToken($id)
    {
        return view('admin.deletetoken', array('id' => $id));
    }

    /**
     * Process the request to delete a token
     *
     * @param  string The token id
     * @return \Illuminate\View\Factory view
     */
    public function postDeleteToken($id)
    {
        $t = new TokensController();
        $t->deleteToken($id);

        return view('admin.deletetokensuccess', array('id' => $id));
    }

    /**
     * Show a list of known clients
     *
     * @return \Illuminate\View\Factory view
     */
    public function listClients()
    {
        $clients = DB::table('clients')->get();

        return view('admin.listclients', array('clients' => $clients));
    }

    /**
     * Form to add a client name
     *
     * @return \Illuminate\View\Factory view
     */
    public function newClient()
    {
        return view('admin.newclient');
    }

    /**
     * Process the request to adda new client name
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postNewClient(Request $request)
    {
        $client_url = $request->input('client_url');
        $client_name = $request->input('client_name');
        DB::table('clients')->insert(
            array(
                'client_url' => $client_url,
                'client_name' => $client_name
            )
        );

        return view('admin.newclientsuccess');
    }

    /**
     * Show a form to edit a client name
     *
     * @param  string The client id
     * @return \Illuminate\View\Factory view
     */
    public function editClient($id)
    {
        $client = DB::table('clients')->where('id', $id)->first();

        return view('admin.editclient', array('id' => $id, 'client_url' => $client['client_url'], 'client_name' => $client['client_name']));
    }

    /**
     * Process the request to edit a client name
     *
     * @param  string  The client id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postEditClient($id, Request $request)
    {
        if ($request->input('edit')) {
            $client_url = $request->input('client_url');
            $client_name = $request->input('client_name');

            DB::table('clients')->where('id', $id)
                ->update(array(
                    'client_url' => $client_url,
                    'client_name' => $client_name
                ));

            return view('admin.editclientsuccess');
        } elseif ($request->input('delete')) {
            DB::table('clients')->where('id', $id)->delete();

            return view('admin.deleteclientsuccess');
        }
    }

    /**
     * Display the form to add a new contact
     *
     * @return \Illuminate\View\Factory view
     */
    public function newContact()
    {
        return view('admin.newcontact');
    }

    /**
     * List the currect contacts that can be edited
     *
     * @return \Illuminate\View\Factory view
     */
    public function listContacts()
    {
        $contacts = Contact::all();

        return view('admin.listcontacts', array('contacts' => $contacts));
    }

    /**
     * Show the form to edit an existing contact
     *
     * @param  string  The contact id
     * @return \Illuminate\View\Factory view
     */
    public function editContact($id)
    {
        $contact = Contact::findOrFail($id);

        return view('admin.editcontact', array('contact' => $contact));
    }

    /**
     * Show the fomr to confirm deleting a contact
     *
     * @return \Illuminate\View\Factory view
     */
    public function deleteContact($id)
    {
        return view('admin.deletecontact', array('id' => $id));
    }

    /**
     * Process the request to add a new contact
     *
     * @param  \Illuminate\Http|request $request
     * @return \Illuminate\View\Factory view
     */
    public function postNewContact(Request $request)
    {
        $contact = new Contact();
        $contact->name = $request->input('name');
        $contact->nick = $request->input('nick');
        $contact->homepage = $request->input('homepage');
        $contact->twitter = $request->input('twitter');
        $contact->save();
        $id = $contact->id;

        return view('admin.newcontactsuccess', array('id' => $id));
    }

    /**
     * Process the request to edit a contact
     *
     * @todo   Allow saving profile pictures for people without homepages
     *
     * @param  string  The contact id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postEditContact($id, Request $request)
    {
        $contact = Contact::findOrFail($id);
        $contact->name = $request->input('name');
        $contact->nick = $request->input('nick');
        $contact->homepage = $request->input('homepage');
        $contact->twitter = $request->input('twitter');
        $contact->save();

        if ($request->hasFile('avatar')) {
            if ($request->input('homepage') != '') {
                $dir = parse_url($request->input('homepage'))['host'];
                $destination = public_path() . '/assets/profile-images/' . $dir;
                $fs = new Filesystem();
                if ($fs->isDirectory($destination) === false) {
                    $fs->makeDirectory($destination);
                }
                $request->file('avatar')->move($destination, 'image');
            }
        }

        return view('admin.editcontactsuccess');
    }

    /**
     * Process the request to delete a contact
     *
     * @param  string  The contact id
     * @return \Illuminate\View\Factory view
     */
    public function postDeleteContact($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return view('admin.deletecontactsuccess');
    }

    /**
     * Download the avatar for a contact
     *
     * This method attempts to find the microformat marked-up profile image
     * from a given homepage and save it accordingly
     *
     * @param  string  The contact id
     * @return \Illuminate\View\Factory view
     */
    public function getAvatar($id)
    {
        $contact = Contact::findOrFail($id);
        $homepage = $contact->homepage;
        if (($homepage !== null) && ($homepage !== '')) {
            $client = new Client();
            try {
                $response = $client->get($homepage);
                $html = (string)$response->getBody();
                $mf2 = \Mf2\parse($html, $homepage);
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                return "Bad Response from $homepage";
            }
            $avatarURL = null; # Initialising
            foreach ($mf2['items'] as $microformat) {
                if ($microformat['type'][0] == 'h-card') {
                    $avatarURL = $microformat['properties']['photo'][0];
                    break;
                }
            }
            try {
                $avatar = $client->get($avatarURL);
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                return "Unable to get $avatarURL";
            }
            $directory = public_path() . '/assets/profile-images/' . parse_url($homepage)['host'];
            $fs = new Filesystem();
            if ($fs->isDirectory($directory) === false) {
                $fs->makeDirectory($directory);
            }
            $fs->put($directory . '/image', $avatar->getBody());
            return view('admin.getavatarsuccess', array('homepage' => parse_url($homepage)['host']));
        }
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
