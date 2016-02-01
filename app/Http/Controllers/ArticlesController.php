<?php

namespace App\Http\Controllers;

use App\Article;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Jonnybarnes\IndieWeb\Numbers;
use Jonnybarnes\UnicodeTools\UnicodeTools;
use League\CommonMark\CommonMarkConverter;

class ArticlesController extends Controller
{
    /**
     * Show all articles (with pagination).
     *
     * @return \Illuminate\View\Factory view
     */
    public function showAllArticles($year = null, $month = null)
    {
        $articles = Article::where('published', '1')
                    ->date($year, $month)
                    ->orderBy('updated_at', 'desc')
                    ->simplePaginate(5);

        return view('multipost', ['data' => $articles]);
    }

    /**
     * Show a single article.
     *
     * @return \Illuminate\View\Factory view
     */
    public function singleArticle($year, $month, $slug)
    {
        $article = Article::where('titleurl', $slug)->first();
        if ($article->updated_at->year != $year || $article->updated_at->month != $month) {
            throw new \Exception;
        }

        return view('singlepost', ['article' => $article]);
    }

    /**
     * We only have the ID, work out post title, year and month
     * and redirect to it.
     *
     * @return \Illuminte\Routing\RedirectResponse redirect
     */
    public function onlyIdInUrl($inURLId)
    {
        $numbers = new Numbers();
        $realId = $numbers->b60tonum($inURLId);
        $article = Article::findOrFail($realId);
        $redirect = '/blog/'
                    . $article->updated_at->year
                    . '/'
                    . $article->updated_at->format('m')
                    . '/'
                    . $article->titleurl;

        return redirect($redirect);
    }

    /**
     * Returns the RSS feed.
     *
     * @return \Illuminate\Http\Response
     */
    public function makeRSS()
    {
        $carbon = new Carbon();
        $pubdates = [];
        $articles = Article::where('published', '1')->where('deleted', '0')->orderBy('date_time', 'desc')->get();
        foreach ($articles as $article) {
            $article['pubdate'] = $carbon->createFromTimeStamp($article['date_time'])->toRSSString();
            $pubdates[] = $article['pubdate'];
        }

        $last = array_pop($pubdates);

        $contents = (string) view('rss', ['data' => $articles, 'pubdate' => $last]);

        return (new Response($contents, '200'))->header('Content-Type', 'application/rss+xml');
    }
}
