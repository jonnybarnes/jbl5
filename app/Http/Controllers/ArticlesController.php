<?php

namespace App\Http\Controllers;

use App\Article;
use Carbon\Carbon;
use Jonnybarnes\Posse\URL;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Jonnybarnes\UnicodeTools\UnicodeTools;
use League\CommonMark\CommonMarkConverter;

class ArticlesController extends Controller
{
    /**
     * Show all articles (with pagination)
     *
     * @return \Illuminate\View\Factory view
     */
    public function showAllArticles($year = null, $month = null)
    {
        $carbon = new Carbon();

        $articles = Article::where('published', '1')->date($year, $month)->orderBy('updated_at', 'desc')->simplePaginate(5);

        foreach ($articles as $article) {
            $article['main'] = $this->markdown($article['main']);
            $article['w3c_time'] = $carbon->createFromTimeStamp($article['date_time'])->toW3CString();
            $article['tooltip_time'] = $carbon->createFromTimeStamp($article['date_time'])->toRFC850String();
            $article['human_time'] = $carbon->createFromTimeStamp($article['date_time'])->diffForHumans();
        }

        return view('multipost', array('data' => $articles));
    }

    /**
     * Show a single article
     *
     * @return \Illuminate\View\Factory view
     */
    public function singleArticle($year, $month, $slug)
    {
        $carbon = new Carbon();
        $article = Article::where('titleurl', $slug)->first();
        $articleCarbon = $carbon->createFromTimeStamp($article->date_time);
        if ($articleCarbon->year != $year || $articleCarbon->month != $month) {
            throw new \Exception;
        }
        $article->main = $this->markdown($article->main);
        $article->w3c_time = $carbon->createFromTimeStamp($article->date_time)->toW3CString();
        $article->tooltip_time = $carbon->createFromTimeStamp($article->tooltip_time)->toRFC850String();
        $article->human_time = $carbon->createFromTimeStamp($article->date_time)->diffForHumans();

        return view('singlepost', array('article' => $article));
    }

    /**
     * We only have the ID, work out post title, year and month
     * and redirect to it.
     *
     * @return \Illuminte\Routing\RedirectResponse redirect
     */
    public function onlyIdInUrl($postId)
    {
        $url = new URL();
        $realId = $url->b60tonum($postId);
        $article = Article::find($realId);
        $slug = $article['titleurl'];
        $published = $article['date_time'];
        $carbon = new Carbon();
        $datetime = $carbon->createFromTimeStamp($published);
        $year = $datetime->year;
        $month = $datetime->month;
        $redirect = '/blog/' . $year . '/' . $month . '/' . $slug;

        return redirect($redirect);
    }

    /**
     * Returns the RSS feed
     *
     * @return \Illuminate\Http\Response
     */
    public function makeRSS()
    {
        $carbon = new Carbon();
        $pubdates = array();
        $articles = Article::where('published', '1')->where('deleted', '0')->orderBy('date_time', 'desc')->get();
        foreach ($articles as $article) {
            $article['main'] = $this->markdown($article['main']);
            $article['pubdate'] = $carbon->createFromTimeStamp($article['date_time'])->toRSSString();
            $pubdates[] = $article['pubdate'];
        }

        $last = array_pop($pubdates);

        $contents = (string) view('rss', array('data' => $articles, 'pubdate' => $last));

        return (new Response($contents, '200'))->header('Content-Type', 'application/rss+xml');
    }

    /**
     * This applies the Commonmark Markdown transform, before though
     * it applies my \uXXXXX\ to chr transform
     *
     * @param  string
     * @return string
     */
    public function markdown($text)
    {
        $unicode = new UnicodeTools();
        $codepoints = $unicode->convertUnicodeCodepoints($text);
        $markdown = new CommonMarkConverter();
        $transformed = $markdown->convertToHtml($codepoints);

        //change <pre><code>[lang] -> <pre><code data-language="lang">
        $match = '/<pre><code>\[(.*)\]\n/';
        $replace = '<pre><code class="language-$1">';
        $text = preg_replace($match, $replace, $transformed);
        $default = preg_replace('/<pre><code>/', '<pre><code class="language-markdown">', $text);

        return $default;
    }

    /**
     * Creates a dynamic link to the article.
     * That is a link of the form /blog/1999/11/i-am-a-slug
     *
     * @param  string  An UNIX epoch timestamp
     * @param  string  A slug of blog post
     * @return string
     */
    public static function createLink($datetime, $titleurl)
    {
        $linkyear = date("Y", $datetime);
        $linkmonth = date("m", $datetime);
        $link = '/blog/' . $linkyear . '/' . $linkmonth . '/' . $titleurl;
        return $link;
    }
}
