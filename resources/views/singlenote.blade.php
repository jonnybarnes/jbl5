@extends('master')

@section('title')
Notes « Jonny Barnes
@stop

@section('content')
<div class="h-entry">
@if ($note->twitter)
  {!! $note->twitter->html !!}
@elseif ($note->in_reply_to)
  <div class="p-in-reply-to h-cite reply-to">
    In reply to <a href="{{ $note->in_reply_to }}" class="u-url">{{ $note->in_reply_to }}</a>
  </div>
@endif
  <div class="note">
    <div class="e-content p-name">
      {!! $note->note !!}
      @if(count($note->photoURLs) > 0)
        @foreach($note->photoURLs as $photoURL)
          <img src="{{ $photoURL }}" alt="" class="note-photo">
        @endforeach
      @endif
    </div>
    <div class="note-metadata">
      <a class="u-url" href="/notes/{{ $note->nb60id }}"><time class="dt-published" datetime="{{ $note->iso8601_time }}">{{ $note->human_time }}</time></a>@if($note->client_id) via <a class="client" href="{{ $note->client_id }}">{{ $note->client_name }}</a>@endif
      @if($note->address)<span class="note-address p-location">in <span class="p-name">{{ $note->address }}</span></span>@endif
      @if($note->replies > 0) - <span class="reply-count"><i class="fa fa-comments"></i> {{ $note->replies }}</span>@endif
      @if($note->tweet_id)<span class="social-links"><a class="u-syndication" href="https://twitter.com/jonnybarnes/status/{{ $note->tweet_id }}"><img src="/assets/svg/twitter-logo.svg" alt="Twitter"></a> - <indie-action do="reply" with="/notes/{{ $note->nb60id }}"><a href="https://twitter.com/intent/tweet?in_reply_to={{ $note->tweet_id }}"><img src="/assets/svg/reply.svg" alt="reply"></a></indie-action> <indie-action do="repost" with="/notes/{{ $note->nb60id }}"><a href="https://twitter.com/intent/retweet?tweet_id={{ $note->tweet_id }}"><img src="/assets/svg/repost.svg" alt="repost"</a></indie-action> <indie-action do="like" with="/notes/{{ $note->nb60id }}"><a href="https://twitter.com/intent/favorite?tweet_id={{ $note->tweet_id }}"><img src="/assets/svg/like.svg" alt="like"></a></indie-action></span>@endif
@if ($note->latitude)
      <div class="map" data-latitude="{{ $note->latitude }}" data-longitude="{{ $note->longitude }}"></div>
@endif
    </div>
  </div>
@foreach($replies as $reply)
  <div class="reply p-comment h-cite">
    <a class="h-card vcard mini-h-card p-author" href="{{ $reply['url'] }}">
      <img src="{{ $reply['photo'] }}" alt="" class="photo u-photo logo"> <span class="fn">{{ $reply['name'] }}</span>
    </a> said at <a class="dt-published" href="{{ $reply['source'] }}">{{ $reply['date'] }}</a>
    <div class="e-content p-name">
      {!! $reply['reply'] !!}
    </div>
  </div>
@endforeach
</div>
@if(count($likes) > 0)<h1 class="notes-subtitle">Likes</h1>@endif
@foreach($likes as $like)
<a href="{{ $like['url'] }}"><img src="{{ $like['photo'] }}" alt="" class="like-photo"></a>
@endforeach
@if(count($reposts) > 0)<h1 class="notes-subtitle">Reposts</h1>@endif
@foreach($reposts as $repost)
<p><a class="h-card vcard mini-h-card p-author" href="{{ $repost['url'] }}">
    <img src="{{ $repost['photo'] }}" alt="profile picture of {{ $repost['name'] }}" class="photo u-photo logo"> <span class="fn">{{ $repost['name'] }}</span>
  </a> reposted this at <a href="{{ $repost['repost'] }}">{{ $repost['date'] }}</a>.</p>
@endforeach
@stop

@section('scripts')
<script src="/assets/js/libs/mapbox.v2.2.1.js"></script>
<script src="/assets/js/libs/Autolinker.v0.15.0.min.js"></script>
<script src="{{ elixir('assets/js/links.js') }}"></script>
<script src="{{ elixir('assets/js/maps.js') }}"></script>
<script src="//twemoji.maxcdn.com/twemoji.min.js"></script>
<script>
  twemoji.parse(document.body);
</script>
<script src="/assets/js/libs/prism.20150827.js"></script>
<script>window.twttr = (function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0],
    t = window.twttr || {};
  if (d.getElementById(id)) return t;
  js = d.createElement(s);
  js.id = id;
  js.src = "https://platform.twitter.com/widgets.js";
  fjs.parentNode.insertBefore(js, fjs);

  t._e = [];
  t.ready = function(f) {
    t._e.push(f);
  };

  return t;
}(document, "script", "twitter-wjs"));
</script>
@stop
