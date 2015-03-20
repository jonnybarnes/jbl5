<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
<channel>
  <title>Jonny Barnes.uk</title>
  <description>An RSS feed of the blog posts found on jonnybarnes.uk</description>
  <link>https://jonnybarnes.uk</link>
  <lastBuildDate>{{ $data[0]['pubdate'] }}</lastBuildDate>
  <pubDate>{{ $pubdate }}</pubDate>
  <ttl>1800</ttl>

  @foreach($data as $article)
  @if($article['url'] == '')
  <item>
  	<title>{{ strip_tags($article['title']) }}</title>
  	<description><![CDATA[{{ $article['main'] }}]]></description>
  	<link>https://{{ config('url.longurl') }}/{{ App\Http\Controllers\ArticlesController::createLink($article['date_time'], $article['titleurl']) }}</link>
  	<guid>https://{{ config('url.longurl') }}/{{ App\Http\Controllers\ArticlesController::createLink($article['date_time'], $article['titleurl']) }}</guid>
  	<pubDate>{{ $article['pubdate'] }}</pubDate>
  </item>
  @else
  <item>
    <title>{{ strip_tags($article['title']) }}</title>
    <description><![CDATA[{{ $article['main'] }}<p><a href="https://{{ config('url.longurl') }}/{{ App\Http\Controllers\ArticlesController::createLink($article['date_time'], $article['titleurl']) }}">Permalink</a></p>]]></description>
    <link>{{ $article['url'] }}</link>
    <guid>http://{{ config('url.longurl') }}{{ App\Http\Controllers\ArticlesController::createLink($article['date_time'], $article['titleurl']) }}</guid>
    <pubDate>{{ $article['pubdate'] }}</pubDate>
  </item>
  @endif
  @endforeach

</channel>
</rss>