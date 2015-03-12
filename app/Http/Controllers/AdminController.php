<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Filesystem\Filesystem;
use App\Article;
use App\Note;
use App\Contact;
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

    public function __construct()
    {
        $this->imageResizeLimit = 800;
        $this->username = env('ADMIN_USER');
    }

    public function showWelcome()
    {
        return view('admin.welcome', array('name' => $this->username));
    }

    //*** ARTICLES ***
    public function newArticle()
    {
        $message = session('message');
        return view('admin.newarticle', array('message' => $message));
    }

    public function listArticles()
    {
        $posts = Article::select('id', 'title', 'published')->where('deleted', '0')->orderBy('id', 'desc')->get();
        return view('admin.listarticles', array('posts' => $posts));
    }

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

    public function deleteArticle($id)
    {
        return view('admin.deletearticle', array('id' => $id));
    }

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

    public function postDeleteArticle($id)
    {
        Article::where('id', $id)->update(array('deleted' => '1'));
        return view('admin.deletearticlesuccess', array('id' => $id));
    }


    //*** NOTES ***
    public function newNote()
    {
        return view('admin.newnote');
    }

    public function listNotes()
    {
        $notes = Note::select('id', 'note')->where('deleted', '0')->orderBy('id', 'desc')->get();
        return view('admin.listnotes', array('notes' => $notes));
    }

    public function editNote($id)
    {
        $note = Note::find($id);
        return view('admin.editnote', array('id' => $id, 'note' => $note));
    }

    public function postNewNote($api = false, $note = null, $replyTo = null, $location = null, $sendtweet = null, $client_id = null, $photo = null)
    {
        $noteprep = new NotePrep();
        if ($note) {
            $noteOrig = $note;
        } else {
            $noteOrig = Input::get('note');
        }
        $noteNfc = \Patchwork\Utf8::filter($noteOrig);
        if (isset($replyTo)) { //this really needs to be refactored
            if ($replyTo == '') {
                $replyTo = null;
            }
        } else {
            $inputReplyTo = Input::get('reply-to');
            if ($inputReplyTo) {
                if ($inputReplyTo == '') {
                    $replyTo = null;
                } else {
                    $replyTo = $inputReplyTo;
                }
            } else {
                $replyTo = null;
            }
        } //This really needs to be refactored
        //so now $replyTo is `null` or has a non-empty value


        //location for a non-API call
        if(Input::get('confirmlocation')) {
            if(Input::get('location')) {
                $formLocation = Input::get('location');
                if(Input::get('address')) {
                    $locadd = $formLocation . ':' . Input::get('address');
                } else {
                    $locadd = $formLocation;
                }
            }
        } else {
            $locadd = null;
        }

        if($location) {
            $locadd = $location;
        }
        
        $time = time();


        if(Input::hasFile('photo')) {
            $hasPhoto = true;
        } elseif($photo) {
            $hasPhoto = true;
        } else {
            $hasPhoto = null;
        }
        
        try {
            $id = Note::insertGetId(
                array(
                    'note' => $noteNfc,
                    'timestamp' => $time,
                    //'author' => $username,
                    'reply_to' => $replyTo,
                    'location' => $locadd,
                    'client_id' => $client_id,
                    'photo' => $hasPhoto
                )
            );
        } catch(Exception $e) {
            $msg = $e->getMessage(); //do something
            return 'Error saving note' . $msg;
            die();
        }

        $url = new URL();
        $realid = $url->numto60($id);
        $photoFilename = 'note-' . $realid;
        if($photo) {
            $ext = explode('.', $photo)[1];
            $photoFilename .= '.' . $ext;
            $fs = new FileSystem();
            $start = public_path() . '/assets/img/notes/' . $photo;
            $end = public_path() . '/assets/img/notes/' . $photoFilename;
            $fs->move($start, $end);
        } elseif(Input::hasFile('photo')) {
            $photo = true;
            $path = public_path() . '/assets/img/notes/';
            $ext = Input::file('photo')->getClientOriginalExtension();
            $photoFilename .= '.' . $ext;
            Input::file('photo')->move($path, $photoFilename);
        }

        $tags = $noteprep->getTags($noteNfc);
        $tagsToSave = [];
        foreach($tags as $tag) {
            $tag_search = Tag::where('tag', $tag)->get();
            if(count($tag_search) == 0) {
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
        foreach($tagsToSave as $tag_id => $tag) {
            $note->tags()->attach($tag_id);
        }
        $longurl = 'https://' . Config::get('url.longurl') . '/notes/' . $realid;
        $webmentions = null; //initialise variable
        if($replyTo !== null) {
            //now we check if webmentions should be sent
            if(Input::get('webmentions') || $api == true) {
                $wm = new WebmentionsController();
                $webmentions = $wm->send($replyTo, $longurl);
            }
        }

        $shorturlId = 't/' . $url->numto60($id);
        $shorturlBase = Config::get('url.shorturl');
        $shorturl = 'https://' . $shorturlBase . '/' . $shorturlId;
        $noteNfcNamesSwapped = $this->swapNames($noteNfc);
        $tweet = '';
        if(Input::get('twitter') || $sendtweet == true) {
            $tweet = $noteprep->createNote($noteNfcNamesSwapped, $shorturlBase, $shorturlId, 140, true, true);
            $tweet_opts = array('status' => $tweet, 'format' => 'json');
            if($replyTo) {
                $tweet_opts['in_reply_to_status_id'] = $noteprep->replyTweetId($replyTo);
            }
            if($locadd) {
                $explode = explode(':', $locadd);
                if(count($explode) == 2) {
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
                if($place_id) {
                    $tweet_opts['place_id'] = $place_id;
                }
            }
            if($photo) {
                $filenameParts = explode('.', $photoFilename);
                $preExt = count($filenameParts) - 2;
                $filenameParts[$preExt] .= '-small';
                $photoFilenameSmall = implode('.', $filenameParts); 
                $imagine = new Imagine();
                $orig = $imagine->open(public_path() . '/assets/img/notes/' . $photoFilename);
                $size = array($orig->getSize()->getWidth(), $orig->getSize()->getHeight());
                if($size[0] > $this->imageResizeLimit || $size[1] > $this->imageResizeLimit) {
                    $ar = $size[0]/$size[1];
                    if($ar >= 1) {
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
                if($photo) {
                    $response_json = Twitter::postTweetMedia($tweet_opts);
                } else {
                    $response_json = Twitter::postTweet($tweet_opts);
                }
                $response = json_decode($response_json);
                $tweet_id = $response->id;
                Note::find($id)->update(array('tweet_id' => $tweet_id));
            } catch(Exception $e) {
                $tweet = 'Error sending tweet. <pre>' . $response_json . '</pre>';
            }
        }

        if($api) {
            return $longurl;
        } else {
            return View::make('newnotesuccess', array('id' => $id, 'shorturl' => $shorturl, 'tweet' => $tweet, 'webmentions' => $webmentions));
        }
    }

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

    //*** Tokens ***

    public function showTokens()
    {
        $t = new TokensController();
        $tokens = $t->getAll();

        return view('admin.listtokens', array('tokens' => $tokens));
    }

    public function deleteToken($id)
    {
        return view('admin.deletetoken', array('id' => $id));
    }

    public function postDeleteToken($id)
    {
        $t = new TokensController();
        $t->deleteToken($id);

        return view('admin.deletetokensuccess', array('id' => $id));
    }

    //*** Clients ***

    public function listClients()
    {
        $clients = DB::table('clients')->get();

        return view('admin.listclients', array('clients' => $clients));
    }

    public function newClient()
    {
        return view('admin.newclient');
    }

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

    public function editClient($id)
    {
        $client = DB::table('clients')->where('id', $id)->first();

        return view('admin.editclient', array('id' => $id, 'client_url' => $client['client_url'], 'client_name' => $client['client_name']));
    }

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

    //*** Contacts ***

    public function newContact()
    {
        return view('admin.newcontact');
    }

    public function listContacts()
    {
        $contacts = Contact::all();

        return view('admin.listcontacts', array('contacts' => $contacts));
    }

    public function editContact($id)
    {
        $contact = Contact::findOrFail($id);

        return view('admin.editcontact', array('contact' => $contact));
    }

    public function deleteContact($id)
    {
        return view('admin.deletecontact', array('id' => $id));
    }

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

    public function postDeleteContact($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return view('admin.deletecontactsuccess');
    }

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

    public function swapNames($note)
    {
        $regex = '/\[.*?\](*SKIP)(*F)|@(\w+)/'; //match @alice but not [@bob](...)
        $tweet = preg_replace_callback(
            $regex,
            function ($matches) {
                try {
                    $contact = Contact::where('nick', '=', mb_strtolower($matches[1]))->firstOrFail();
                } catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
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
