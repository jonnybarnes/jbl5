<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['domain' => config('url.longurl')], function () {
    //Static homepage
    get('/', function () {
        return view('homepage');
    });

    //Static project page
    get('projects', function () {
        return view('projects');
    });

    //The login routes to get authe'd for admin
    get('login', ['as' => 'login', function () {
        return view('login');
    }]);
    post('login', 'AuthController@login');

    //Admin pages grouped for filter
    Route::group(['middleware' => 'myauth'], function () {
        get('admin', 'AdminController@showWelcome');

        //Articles
        get('admin/blog/new', 'ArticlesAdminController@newArticle');
        get('admin/blog/edit', 'ArticlesAdminController@listArticles');
        get('admin/blog/edit/{id}', 'ArticlesAdminController@editArticle');
        get('admin/blog/delete/{id}', 'ArticlesAdminController@deleteArticle');
        post('admin/blog/new', 'ArticlesAdminController@postNewArticle');
        post('admin/blog/edit/{id}', 'ArticlesAdminController@postEditArticle');
        post('admin/blog/delete/{id}', 'ArticlesAdminController@postDeleteArticle');

        //Notes
        get('admin/note/new', 'NotesAdminController@newNote');
        get('admin/note/edit', 'NotesAdminController@listNotes');
        get('admin/note/edit/{id}', 'NotesAdminController@editNote');
        post('admin/note/new', 'NotesAdminController@postNewNote');
        post('admin/note/edit/{id}', 'NotesAdminController@postEditNote');

        //Tokens
        get('admin/tokens', 'TokensAdminController@showTokens');
        get('admin/tokens/delete/{id}', 'TokensAdminController@deleteToken');
        post('admin/tokens/delete/{id}', 'TokensAdminController@postDeleteToken');

        //Micropub Clients
        get('admin/clients', 'ClientsAdminController@listClients');
        get('admin/clients/new', 'ClientsAdminController@newClient');
        get('admin/clients/edit/{id}', 'ClientsAdminController@editClient');
        post('admin/clients/new', 'ClientsAdminController@postNewClient');
        post('admin/clients/edit/{id}', 'ClientsAdminController@postEditClient');

        //Contacts
        get('admin/contacts/new', 'ContactsAdminController@newContact');
        get('admin/contacts/edit', 'ContactsAdminController@listContacts');
        get('admin/contacts/edit/{id}', 'ContactsAdminController@editContact');
        get('admin/contacts/edit/{id}/getavatar', 'ContactsAdminController@getAvatar');
        get('admin/contacts/delete/{id}', 'ContactsAdminController@deleteContact');
        post('admin/contacts/new', 'ContactsAdminController@postNewContact');
        post('admin/contacts/edit/{id}', 'ContactsAdminController@postEditContact');
        post('admin/contacts/delete/{id}', 'ContactsAdminController@postDeleteContact');

        //Places
        get('admin/places/new', 'PlacesAdminController@newPlace');
        get('admin/places/edit', 'PlacesAdminController@listPlaces');
        get('admin/places/edit/{id}', 'PlacesAdminController@editPlace');
        post('admin/places/new', 'PlacesAdminController@postNewPlace');
        post('admin/places/edit/{id}', 'PlacesAdminController@postEditPlace');
    });

    //Blog pages using ArticlesController
    get('blog/s/{id}', 'ArticlesController@onlyIdInURL');
    get('blog/{year?}/{month?}', 'ArticlesController@showAllArticles');
    get('blog/{year}/{month}/{slug}', 'ArticlesController@singleArticle');

    //micropub new notes page
    //this needs to be first so `notes/new` doesn't match `notes/{id}`
    get('notes/new', 'MicropubClientController@newNotePage');
    post('notes/new', 'MicropubClientController@postNewNote');

    //Notes pages using NotesController
    get('notes', 'NotesController@showNotes');
    get('note/{id}', 'NotesController@singleNoteRedirect');
    get('notes/{id}', 'NotesController@singleNote');
    get('notes/tagged/{tag}', 'NotesController@taggedNotes');

    //indieauth
    Route::any('beginauth', 'IndieAuthController@beginauth');
    get('auth', 'IndieAuthController@indieauth');
    get('logout', 'IndieAuthController@indieauthLogout');

    //micropub endoints
    post('api/token', 'MicropubController@tokenEndpoint');
    post('api/post', 'MicropubController@post');
    get('api/post', 'MicropubController@getEndpoint');

    //micropub refresh syndication targets
    get('refresh-syndication-targets', 'MicropubClientController@refreshSyndicationTargets');

    //webmention
    get('webmention', function () {
        return view('webmention-endpoint');
    });
    post('webmention', 'WebMentionsController@receive');

    //Contacts
    get('contacts', 'ContactsController@showAll');
    get('contacts/{nick}', 'ContactsController@showSingle');

    //Places
    get('places', 'PlacesController@index');
    get('places/{slug}', 'PlacesController@show');
    //Places micropub
    get('places/near/{lat}/{lng}', 'MicropubClientController@nearbyPlaces');
    post('places/new', 'MicropubClientController@postNewPlace');

    get('feed', 'ArticlesController@makeRSS');
});

//Short URL
Route::group(['domain' => config('url.shorturl')], function () {
    get('/', 'ShortURLsController@baseURL');
    get('@', 'ShortURLsController@twitter');
    get('+', 'ShortURLsController@googlePlus');
    get('α', 'ShortURLsController@appNet');

    get('{type}/{id}', 'ShortURLsController@expandType')->where(
        [
            'type' => '[bt]',
            'id' => '[0-9A-HJ-NP-Z_a-km-z]+',
        ]
    );

    get('h/{id}', 'ShortURLsController@redirect');
    get('{id}', 'ShortURLsController@oldRedirect')->where(
        [
            'id' => '[0-9A-HJ-NP-Z_a-km-z]{4}',
        ]
    );
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    //
});
