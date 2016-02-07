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

Route::group(['domain' => config('url.longurl'), 'middleware' => 'web'], function () {
    //Static homepage
    Route::get('/', function () {
        return view('homepage');
    });

    //Static project page
    Route::get('projects', function () {
        return view('projects');
    });

    //The login routes to get authe'd for admin
    Route::get('login', ['as' => 'login', function () {
        return view('login');
    }]);
    Route::post('login', 'AuthController@login');

    //Admin pages grouped for filter
    Route::group(['middleware' => 'myauth'], function () {
        Route::get('admin', 'AdminController@showWelcome');

        //Articles
        Route::get('admin/blog/new', 'ArticlesAdminController@newArticle');
        Route::get('admin/blog/edit', 'ArticlesAdminController@listArticles');
        Route::get('admin/blog/edit/{id}', 'ArticlesAdminController@editArticle');
        Route::get('admin/blog/delete/{id}', 'ArticlesAdminController@deleteArticle');
        Route::post('admin/blog/new', 'ArticlesAdminController@postNewArticle');
        Route::post('admin/blog/edit/{id}', 'ArticlesAdminController@postEditArticle');
        Route::post('admin/blog/delete/{id}', 'ArticlesAdminController@postDeleteArticle');

        //Notes
        Route::get('admin/note/new', 'NotesAdminController@newNote');
        Route::get('admin/note/edit', 'NotesAdminController@listNotes');
        Route::get('admin/note/edit/{id}', 'NotesAdminController@editNote');
        Route::post('admin/note/new', 'NotesAdminController@postNewNote');
        Route::post('admin/note/edit/{id}', 'NotesAdminController@postEditNote');

        //Tokens
        Route::get('admin/tokens', 'TokensAdminController@showTokens');
        Route::get('admin/tokens/delete/{id}', 'TokensAdminController@deleteToken');
        Route::post('admin/tokens/delete/{id}', 'TokensAdminController@postDeleteToken');

        //Micropub Clients
        Route::get('admin/clients', 'ClientsAdminController@listClients');
        Route::get('admin/clients/new', 'ClientsAdminController@newClient');
        Route::get('admin/clients/edit/{id}', 'ClientsAdminController@editClient');
        Route::post('admin/clients/new', 'ClientsAdminController@postNewClient');
        Route::post('admin/clients/edit/{id}', 'ClientsAdminController@postEditClient');

        //Contacts
        Route::get('admin/contacts/new', 'ContactsAdminController@newContact');
        Route::get('admin/contacts/edit', 'ContactsAdminController@listContacts');
        Route::get('admin/contacts/edit/{id}', 'ContactsAdminController@editContact');
        Route::get('admin/contacts/edit/{id}/getavatar', 'ContactsAdminController@getAvatar');
        Route::get('admin/contacts/delete/{id}', 'ContactsAdminController@deleteContact');
        Route::post('admin/contacts/new', 'ContactsAdminController@postNewContact');
        Route::post('admin/contacts/edit/{id}', 'ContactsAdminController@postEditContact');
        Route::post('admin/contacts/delete/{id}', 'ContactsAdminController@postDeleteContact');

        //Places
        Route::get('admin/places/new', 'PlacesAdminController@newPlace');
        Route::get('admin/places/edit', 'PlacesAdminController@listPlaces');
        Route::get('admin/places/edit/{id}', 'PlacesAdminController@editPlace');
        Route::post('admin/places/new', 'PlacesAdminController@postNewPlace');
        Route::post('admin/places/edit/{id}', 'PlacesAdminController@postEditPlace');
    });

    //Blog pages using ArticlesController
    Route::get('blog/s/{id}', 'ArticlesController@onlyIdInURL');
    Route::get('blog/{year?}/{month?}', 'ArticlesController@showAllArticles');
    Route::get('blog/{year}/{month}/{slug}', 'ArticlesController@singleArticle');

    //micropub new notes page
    //this needs to be first so `notes/new` doesn't match `notes/{id}`
    Route::get('notes/new', 'MicropubClientController@newNotePage');
    Route::post('notes/new', 'MicropubClientController@postNewNote');

    //Notes pages using NotesController
    Route::get('notes', 'NotesController@showNotes');
    Route::get('note/{id}', 'NotesController@singleNoteRedirect');
    Route::get('notes/{id}', 'NotesController@singleNote');
    Route::get('notes/tagged/{tag}', 'NotesController@taggedNotes');

    //indieauth
    Route::any('beginauth', 'IndieAuthController@beginauth');
    Route::get('indieauth', 'IndieAuthController@indieauth');
    Route::get('api/post', 'IndieAuthController@tokenEndpoint');
    Route::get('logout', 'IndieAuthController@indieauthLogout');

    //micropub endoints
    Route::post('api/post', 'MicropubController@post');
    Route::get('api/post', 'MicropubController@getEndpoint');

    //micropub refresh syndication targets
    Route::get('refresh-syndication-targets', 'MicropubClientController@refreshSyndicationTargets');

    //webmention
    Route::get('webmention', function () {
        return view('webmention-endpoint');
    });
    Route::post('webmention', 'WebMentionsController@receive');

    //Contacts
    Route::get('contacts', 'ContactsController@showAll');
    Route::get('contacts/{nick}', 'ContactsController@showSingle');

    //Places
    Route::get('places', 'PlacesController@index');
    Route::get('places/{slug}', 'PlacesController@show');
    //Places micropub
    Route::get('places/near/{lat}/{lng}', 'MicropubClientController@nearbyPlaces');
    Route::post('places/new', 'MicropubClientController@postNewPlace');

    Route::get('feed', 'ArticlesController@makeRSS');
});

//Short URL
Route::group(['domain' => config('url.shorturl'), 'middleware' => 'web'], function () {
    Route::get('/', 'ShortURLsController@baseURL');
    Route::get('@', 'ShortURLsController@twitter');
    Route::get('+', 'ShortURLsController@googlePlus');
    Route::get('α', 'ShortURLsController@appNet');

    Route::get('{type}/{id}', 'ShortURLsController@expandType')->where(
        [
            'type' => '[bt]',
            'id' => '[0-9A-HJ-NP-Z_a-km-z]+',
        ]
    );

    Route::get('h/{id}', 'ShortURLsController@redirect');
    Route::get('{id}', 'ShortURLsController@oldRedirect')->where(
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
