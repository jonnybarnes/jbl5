<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title>Jonny Barnes.uk</title>
  <atom:link href="https://{{ config('url.longurl') }}/feed" rel="self" type="application/rss+xml" />
  <description>An RSS feed of the blog posts found on jonnybarnes.uk</description>
  <link>https://jonnybarnes.uk</link>
  <lastBuildDate>{{ $buildDate }}</lastBuildDate>
  <ttl>1800</ttl>

  @foreach($articles as $article)
  <item>
    <title>{{ strip_tags($article->title) }}</title>
    <description><![CDATA[{{ $article->main }}@if($article->url)<p><a href="https://{{ config('url.longurl') }}{{ $article->link }}">Permalink</a></p>@endif]]></description>
    <link>@if($article->url != ''){{ $article->url }}@else{{ 'https://' . config('url.longurl') }}{{ $article->link }}@endif</link>
    <guid>https://{{ config('url.longurl') }}{{ $article->link }}</guid>
    <pubDate>{{ $article->pubdate }}</pubDate>
  </item>
  @endforeach

</channel>
</rss>
