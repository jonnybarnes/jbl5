<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Article;
use Carbon\Carbon;
use League\CommonMark\CommonMarkConverter;
use Jonnybarnes\UnicodeTools\UnicodeTools;
use Jonnybarnes\Posse\URL;

class ArticlesController extends Controller
{
	/**
	 * Show all articles (with pagination)
	 *
	 * @return view
	 */
	public function manyArticles($year = null, $month = null)
	{
		$carbon = new Carbon();
		$start = null;
		if(isset($year)) {
			if(isset($month)) {
				$start = mktime(0, 0, 0, $month, 1, $year);
				$end = mktime(23, 59, 59, $month+1, 0, $year);
			} else {
				$start = mktime(0, 0, 0, 1, 1, $year);
				$end= mktime(23, 59, 59, 12, 31, $year);
			}
		}

		if($start == null) {
			$articles = Article::where('deleted', '0')->where('published', '1')->orderBy('date_time', 'desc')->simplePaginate(5);
		} else {
			$articles = Article::where('deleted', '0')->where('published', '1')->orderBy('date_time', 'desc')->whereBetween('date_time', array($start, $end))->simplePaginate(5);
		}

		foreach($articles as $article) {
			$article['main'] = $this->Markdown($article['main']);
			$article['w3c_time'] = $carbon->createFromTimeStamp($article['date_time'])->toW3CString();
			$article['tooltip_time'] = $carbon->createFromTimeStamp($article['date_time'])->toRFC850String();
			$article['human_time'] = $carbon->createFromTimeStamp($article['date_time'])->diffForHumans();
		}

		return view('multipost', array('data' => $articles));
	}

	/**
	 * Show a single article
	 *
	 * @return view
	 */
	public function singleArticle($year, $month, $post)
	{
		$carbon = new Carbon();
		$article = Article::where('titleurl', $post)->get();
		foreach($article as $row) {
			$row['main'] = $this->Markdown($row['main']);
			$row['w3c_time'] = $carbon->createFromTimeStamp($row['date_time'])->toW3CString();
			$row['tooltip_time'] = $carbon->createFromTimeStamp($row['date_time'])->toRFC850String();
			$row['human_time'] = $carbon->createFromTimeStamp($row['date_time'])->diffForHumans();
		}
		return view('singlepost', array('data' => $article));
	}

	/**
	 * We only have the ID, work out post title, year and month
	 * and redirect to it.
	 *
	 */
	public function onlyId($id)
	{
		$url = new URL();
		$id = $url->b60tonum($id);
		$article = Article::find($id);
		$slug = $article['titleurl'];
		$published = $article['date_time'];
		$carbon = new Carbon();
		$dt = $carbon->createFromTimeStamp($published);
		$year = $dt->year;
		$month = $dt->month;
		$redirect = '/blog/' . $year . '/' . $month . '/' . $slug;

		return redirect($redirect);
	}

	/**
	 * Returns the RSS feed
	 *
	 * @return Response
	 */
	public function makeRSS()
	{
		$carbon = new Carbon();
		$pubdates = array();
		$articles = Article::where('published', '1')->where('deleted', '0')->orderBy('date_time', 'desc')->get();
		foreach($articles as $article) {
			$article['main'] = $this->Markdown($article['main']);
			$article['pubdate'] = $carbon->createFromTimeStamp($article['date_time'])->toRSSString();
			$pubdates[] = $article['pubdate'];
		}

		$last = array_pop($pubdates);

		$contents = (string) view('rss', array('data' => $articles, 'pubdate' => $last));

		return (new Response($contents, '200'))->header('Content-Type', 'application/rss+xml');
	}

	/**
	 * This applies the dflydev Markdown transform, before though
	 * it applies my \uXXXXX\ to chr transform
	 *
	 * @return string
	 */
	public function Markdown($text) {
		$unicode = new UnicodeTools();
		$codepoints = $unicode->convertUnicodeCodepoints($text);
		$markdown = new CommonMarkConverter();
		$transformed = $markdown->convertToHtml($codepoints);
		$codeblocks = $this->codeBlocksLang($transformed);
		
		return $codeblocks;
	}

	/**
	 * Creates a dynamic link to the article.
	 * That is a link of the form /blog/1999/11/i-am-a-slug
	 *
	 * @return string
	 */
	public static function createLink($date_time, $titleurl) {
		$linkyear = date("Y", $date_time);
		$linkmonth = date("m", $date_time);
		$link = '/blog/' . $linkyear . '/' . $linkmonth . '/' . $titleurl;
		return $link;
	}

	/**
	 * Find a post-Markdown’ed case of <pre><code>[language] and convert
	 * to <pre><code data-language="language">
	 *
	 * @return string
	 */
	public function codeBlocksLang($text)
	{
		$match = '/<pre><code>\[(.*)\]\n/';
		$replace = '<pre><code data-language="$1">';

		$text = preg_replace($match, $replace, $text);
		return $text;
	}
}