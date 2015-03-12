<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <title>@yield('title')</title>
  <meta name="viewport" content="width=device-width">
  <script type="text/javascript" src="//use.typekit.net/kmb3cdb.js"></script>
  <script type="text/javascript">try{Typekit.load();}catch(e){}</script>
  <script type="text/javascript" src="https://api.tiles.mapbox.com/mapbox.js/v2.1.5/mapbox.js"></script>
  <!--[if lt IE 9]>
  <script src="//cdn.jsdelivr.net/html5shiv/3.7.2/html5shiv.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
  <link rel="stylesheet" href="/assets/css/<?php if(App::environment('local')) { echo 'global'; } else { echo 'style'; }?>.css">
  <link rel="stylesheet" href="/assets/css/solarized-dark.css">
  <link rel="stylesheet" href="https://api.tiles.mapbox.com/mapbox.js/v2.1.5/mapbox.css">
  <link rel="stylesheet" href="/assets/font-awesome/css/font-awesome.min.css">
  <link rel="openid.server" href="https://indieauth.com/openid">
  <link rel="openid.delegate" href="https://jonnybarnes.uk">
  <link rel="authorization_endpoint" href="https://indieauth.com/auth">
  <link rel="token_endpoint" href="/api/token">
  <link rel="micropub" href="/api/post">
  <link rel="webmention" href="/webmention">
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
    <section id="content">
@yield('content')
    </section>
  </main>
@section('scripts')
<!--scripts go here when needed-->
@show
<script src="//twemoji.maxcdn.com/twemoji.min.js"></script>
<script>
  twemoji.parse(document.body);
</script>
<?php if(App::environment('production')) { ?>
<!-- Piwik -->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "://piwik.jonnybarnes.uk/";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', 1]);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript';
    g.defer=true; g.async=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<noscript><p><img src="https://piwik.jonnybarnes.uk/piwik.php?idsite=1" style="border:0;" alt="" /></p></noscript>
<!-- End Piwik Code -->
<?php } ?>
</body>
</html>