<?php

namespace App\Http\Controllers;

use App\Article;
use App\Http\Controllers\Controller;

class ArticlesAdminController extends Controller
{
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
    public function editArticle($articleId)
    {
        $post = Article::select(
            'title',
            'main',
            'url',
            'date_time',
            'published'
        )->where('id', $articleId)->get();
        return view('admin.editarticle', array('id' => $articleId, 'post' => $post));
    }

    /**
     * Show the delete confirmation form for an article
     *
     * @param  string  The article id
     * @return \Illuminate\View\Factory view
     */
    public function deleteArticle($articleId)
    {
        return view('admin.deletearticle', array('id' => $articleId));
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
            if ($unique !== false) {
                //We've checked for error 1062, i.e. duplicate titleurl
                return redirect('admin/blog/new')->withInput()->with('message', 'Duplicate title, please change');
            }
            //this isn't the error you're looking for
            throw $e;
        }
        return view('admin.newarticlesuccess', array('id' => $id, 'title' => $title));
    }

    /**
     * Process an incoming request to edit an article
     *
     * @param  string
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate|View\Factory view
     */
    public function postEditArticle($articleId, Request $request)
    {
        $article = Article::find($articleId);
        $article->title = $request->input('title');
        $article->url = $request->input('url');
        $article->main = $request->input('main');
        $article->date_time = strtotime($request->input('time'));
        $article->published = $request->input('published');
        $article->save();

        return view('admin.editarticlesuccess', array('id' => $articleId));
    }

    /**
     * Process a request to delete an aricle
     *
     * @param  string The article id
     * @return \Illuminate\View\Factory view
     */
    public function postDeleteArticle($articleId)
    {
        Article::where('id', $articleId)->update(array('deleted' => '1'));

        return view('admin.deletearticlesuccess', array('id' => $articleId));
    }
}
