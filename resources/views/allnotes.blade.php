@extends('master')

@section('title')
Notes « Jonny Barnes
@stop

@section('content')
<div class="h-feed">
<!-- the following span stops microformat parses going haywire generating
a name property for the h-feed -->
<span class="p-name"></span>
  @foreach ($notes as $note)
  <div class="h-entry">
  @if ($note->in_reply_to)
    <div class="p-in-reply-to h-cite reply-to">
      In reply to <a href="{{ $note->reply_to }}" class="u-url">{{ $note->in_reply_to }}</a>
    </div>
  @endif
    <div class="note">
      <div class="e-content p-name">{!! $note->note !!}@if($note->photopath)<img src="{{ $note->photopath }}" alt="" class="note-photo">@endif</div>
      <div class="note-metadata">
        <a class="u-url" href="/notes/{{ $note->nb60id }}"><time class="dt-published" datetime="{{ $note->iso8601_time }}">{{ $note->human_time }}</time></a>
        @if($note->address)<span class="note-address p-location">in <span class="p-name">{{ $note->address }}</span></span>@endif
        @if($note->replies > 0) - <span class="reply-count"><i class="fa fa-comments"></i> {{ $note->replies }}</span>@endif
        @if($note->tweet_id)<span class="social-links"><a class="u-syndication" href="https://twitter.com/jonnybarnes/status/{{ $note->tweet_id }}"><i class="fa fa-twitter"></i></a> - <indie-action do="reply" with="/notes/{{ $note->nb60id }}"><a href="https://twitter.com/intent/tweet?in_reply_to={{ $note->tweet_id }}"><i class="fa fa-reply"></i></a></indie-action> <indie-action do="repost" with="/notes/{{ $note->nb60id }}"><a href="https://twitter.com/intent/retweet?tweet_id={{ $note->tweet_id }}"><i class="fa fa-retweet"></i></a></indie-action> <indie-action do="like" with="/notes/{{ $note->nb60id }}"><a href="https://twitter.com/intent/favorite?tweet_id={{ $note->tweet_id }}"><i class="fa fa-star-o"></i></a></indie-action></span>@endif
@if ($note->latitude)
        <div class="map" data-latitude="{{ $note->latitude }}" data-longitude="{{ $note->longitude }}"></div>
@endif
      </div>
    </div>
  </div>
@endforeach
</div>
{!! $notes->render() !!}
@stop

@section('scripts')
<script src="/assets/js/Autolinker.min.js"></script>
<script src="/assets/js/links.js"></script>
<script src="/assets/js/maps.js"></script>
@stop
