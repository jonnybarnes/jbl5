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
        @include('templates.note', ['note' => $note])
      </div>
      @endforeach
    </div>
{!! $notes->render() !!}
@stop

@section('scripts')
<script src="/assets/js/libs/mapbox.v2.2.1.js"></script>
<script src="{{ elixir('assets/js/Autolinker.min.js') }}"></script>
<script src="{{ elixir('assets/js/links.js') }}"></script>
<script src="{{ elixir('assets/js/maps.js') }}"></script>
<script src="//twemoji.maxcdn.com/twemoji.min.js"></script>
<script>
  twemoji.parse(document.body);
</script>
<script src="{{ elixir('assets/js/prism.js') }}"></script>
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
