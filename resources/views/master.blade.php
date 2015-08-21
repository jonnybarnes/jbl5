<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <title>@if (App::environment() == 'local'){!! "[testing] -"!!}@endif @yield('title')</title>
  <meta name="viewport" content="width=device-width">
  <link rel="stylesheet" href="/assets/css/normalize.css">
  <link rel="stylesheet" href="/assets/css/<?php if (App::environment('local')) { echo 'global'; } else { echo 'style'; }?>.css">
  <link rel="stylesheet" href="/assets/css/mapbox.css">
  <link rel="stylesheet" href="/assets/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="/assets/css/prism.css">
  <link rel="openid.server" href="https://indieauth.com/openid">
  <link rel="openid.delegate" href="https://jonnybarnes.uk">
  <link rel="authorization_endpoint" href="https://indieauth.com/auth">
  <link rel="token_endpoint" href="https://{{ config('url.longurl') }}/api/token">
  <link rel="micropub" href="https://{{ config('url.longurl') }}/api/post">
  <link rel="webmention" href="https://{{ config('url.longurl') }}/webmention">
  <link rel="shortcut icon" href="/assets/img/jmb-bw.png">
</head>
<body>
  <header id="topheader" role="banner">
    <a rel="author" href="/">
      <h1>Jonny Barnes</h1>
    </a>
    <nav role="navigation">
      <a href="/blog">Articles</a>
      <a href="/notes">Notes</a>
      <a href="/projects">Projects</a>
    </nav>
  </header>

  <main role="main">
    <section>
@yield('content')
    </section>
  </main>
@section('scripts')
<!--scripts go here when needed-->
@show
  <script src="//use.typekit.net/kmb3cdb.js"></script>
  <script>try{Typekit.load({ async: true });}catch(e){}</script>
  <script src="/assets/js/mapbox.js"></script>
  <script src="//twemoji.maxcdn.com/twemoji.min.js"></script>
  <script>
    twemoji.parse(document.body);
  </script>
  <script src="/assets/js/prism.js"></script>
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
</body>
</html>
