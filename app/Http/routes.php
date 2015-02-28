<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(array('domain' => config('url.longurl')), function() {
	//Static homepage
	get('/', function()
	{
		return view('homepage');
	});

	//Static project page
	get('projects', function()
	{
		return view('projects');
	});

	//The login routes to get authe'd for admin
	get('login', ['as' => 'login', function()
	{
		return view('login');
	}]);
    post('login', 'MyAuthController@login');

	//Admin pages grouped for filter
	Route::group(['middleware' => 'myauth'], function() {
		get('admin', 'AdminController@showWelcome');

		get('admin/blog/new', 'AdminController@newArticle');
		get('admin/blog/edit', 'AdminController@listArticles');
		get('admin/blog/edit/{id}', 'AdminController@editArticle');
		get('admin/blog/delete/{id}', 'AdminController@deleteArticle');

		get('admin/note/new', 'AdminController@newNote');
		get('admin/note/edit', 'AdminController@listNotes');
		get('admin/note/edit/{id}', 'AdminController@editNote');
		get('admin/note/delete/{id}', 'AdminController@deleteNote');

		get('admin/tokens', 'AdminController@showTokens');
		get('admin/tokens/delete/{id}', 'AdminController@deleteToken');
		post('admin/tokens/delete/{id}', 'AdminController@postDeleteToken');

		get('admin/clients', 'AdminController@listClients');
		get('admin/clients/new', 'AdminController@newClient');
		post('admin/clients/new', 'AdminController@postNewClient');
		get('admin/clients/edit/{id}', 'AdminController@editClient');
		post('admin/clients/edit/{id}', 'AdminController@postEditClient');

		get('admin/contacts/new', 'AdminController@newContact');
		get('admin/contacts/edit', 'AdminController@listContacts');
		get('admin/contacts/edit/{id}', 'AdminController@editContact');
		get('admin/contacts/edit/{id}/getavatar', 'AdminController@getAvatar');
		get('admin/contacts/delete/{id}', 'AdminController@deleteContact');

		//POSTs from forms
		post('admin/blog/new', 'AdminController@postNewArticle');
		post('admin/blog/edit/{id}', 'AdminController@postEditArticle');
		post('admin/blog/delete/{id}', 'AdminController@postDeleteArticle');

		post('admin/note/new', 'AdminController@postNewNote');
		post('admin/note/edit/{id}', 'AdminController@postEditNote');
		post('admin/note/delete/{id}', 'AdminController@postDeleteNote');

		post('admin/contacts/new', 'AdminController@postNewContact');
		post('admin/contacts/edit/{id}', 'AdminController@postEditContact');
		post('admin/contacts/delete/{id}', 'AdminController@postDeleteContact');
	});

	//Blog pages using ArticlesController
	get('blog', 'ArticlesController@manyArticles');
	get('blog/s/{id}', 'ArticlesController@onlyId');
	get('blog/{year}', 'ArticlesController@manyArticles');
	get('blog/{year}/{month}', 'ArticlesController@manyArticles');
	get('blog/{year}/{month}/{post}', 'ArticlesController@singleArticle');

	//micropub new notes page
	//Route::get('notes/new', 'MicropubController@micropubNewNote');
	//Route::post('notes/new', 'MicropubController@post');

	//Notes pages using NotesController
	get('notes', 'NotesController@showNotes');
	get('note/{id}', 'NotesController@singleNoteRedirect');
	get('notes/{id}', 'NotesController@singleNote');
	get('notes/tagged/{tag}', 'NotesController@taggedNotes');

	//indieauth
	//Route::any('beginauth', 'AuthController@beginauth');
	//Route::get('auth', 'AuthController@indieauth');
	//Route::get('logout', 'AuthController@indieauthLogout');

	//micropub endoints
	//Route::post('api/token', 'MicropubController@tokenEndpoint');
	//Route::post('api/post', 'MicropubController@note');
	//Route::get('api/post', 'MicropubController@tokenVeracity');

	//webmention
	//Route::post('webmention', 'WebmentionsController@recieve');

	//Contacts
	get('contacts', 'ContactsController@showAll');
	get('contacts/{nick}', 'ContactsController@showSingle');

	get('feed', 'ArticlesController@makeRSS');
});

//Short URL
Route::group(array('domain' => config('url.shorturl')), function() {
	get('/', 'ShortURLsController@baseURL');
	get('@', 'ShortURLsController@Twitter');
	get('+', 'ShortURLsController@GooglePlus');
	get('α', 'ShortURLsController@AppNet');

	get('{type}/{id}', 'ShortURLsController@expandType')->where(
		array(
			'type' => '[bt]',
			'id' => '[0-9A-HJ-NP-Z_a-km-z]+'
		));

	get('h/{id}', 'ShortURLsController@redirect');
	get('{id}', 'ShortURLsController@oldRedirect')->where(
		array(
			'id' => '[0-9A-HJ-NP-Z_a-km-z]{4}'
		));
});