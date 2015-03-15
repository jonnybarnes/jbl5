@extends('master')

@section('title')
Notes « Jonny Barnes
@stop

@section('content')
<div class="h-entry">
@if ($note->reply_to)
@if(mb_substr($note->reply_to, 0, 19, "UTF-8") == 'https://twitter.com')
  <div class="p-in-reply-to h-cite reply-to">
    <span class="reply-arrow">↪</span>
    <a class="h-card vcard mini-h-card p-author" href="{{ $note->reply_to_url }}">
      <img src="{{ $note->reply_to_profile_photo }}" alt="" class="photo u-photo logo"> {{ $note->reply_to_author_name }}
    </a>
    <div class="e-content p-name">{!! $note->reply_to_text !!}</div>
  </div>
@else
  <div class="p-in-reply-to h-cite reply-to">
    In reply to <a href="{{ $note->reply_to }}" class="u-url">{{ $note->reply_to }}</a>
  </div>
@endif
@endif
  <div class="note">
    <div class="e-content p-name">{!! $note->note !!}@if($note->photopath)<img src="{{ $note->photopath }}" alt="" class="note-photo">@endif</div>
    <div class="note-metadata">
      <a class="u-url" href="/notes/{{ $note->nb60id }}"><time class="dt-published" datetime="{{ $note->iso8601_time }}">{{ $note->human_time }}</time></a>@if($note->client_id) via <a class="client" href="{{ $note->client_id }}">{{ $note->client_name }}</a>@endif
      @if($note->address)<span class="note-address p-location">in <span class="p-name">{{ $note->address }}</span></span>@endif
      @if($note->replies > 0) - <span class="reply-count"><i class="fa fa-comments"></i> {{ $note->replies }}</span>@endif
      @if($note->tweet_id)<span class="social-links"><a class="u-syndication" href="https://twitter.com/jonnybarnes/status/{{ $note->tweet_id }}"><i class="fa fa-twitter"></i></a> - <indie-action do="reply" with="/notes/{{ $note->nb60id }}"><a href="https://twitter.com/intent/tweet?in_reply_to={{ $note->tweet_id }}"><i class="fa fa-reply"></i></a></indie-action> <indie-action do="repost" with="/notes/{{ $note->nb60id }}"><a href="https://twitter.com/intent/retweet?tweet_id={{ $note->tweet_id }}"><i class="fa fa-retweet"></i></a></indie-action> <indie-action do="like" with="/notes/{{ $note->nb60id }}"><a href="https://twitter.com/intent/favorite?tweet_id={{ $note->tweet_id }}"><i class="fa fa-star-o"></i></a></indie-action></span>@endif
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
<script src="/assets/js/Autolinker.min.js"></script>
<script src="/assets/js/links.js"></script>
<script src="/assets/js/maps.js"></script>
@stop
