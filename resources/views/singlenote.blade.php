@extends('master')

@section('title')
{{ strip_tags($note->note) }} « Notes « Jonny Barnes
@stop

@section('content')
    <div class="h-entry">
      @include('templates.note', ['note' => $note])
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
<script src="{{ elixir('assets/js/Autlinker.min.js') }}"></script>
<script src="{{ elixir('assets/js/links.js') }}"></script>
<script src="{{ elixir('assets/js/maps.js') }}"></script>
<script src="//twemoji.maxcdn.com/twemoji.min.js"></script>
<script>
  twemoji.parse(document.body);
</script>
<script src="{{ elixir('assets/js/libs/prism.js') }}"></script>
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

<link rel="stylesheet" href="{{ elixir('assets/css/prism.css') }}">
@stop
