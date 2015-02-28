@extends('master')
@section('title')Jonny Barnes’ Projects @stop

@section('content')
<div id="projects">
<h2>Projects</h2>
  <h3><a href="https://shaaaaaaaaaaaaa.com">Shaaaaaaaaaaaaa.com</a></h3>
  <p>I’m collaborating on a project with Eric Mill (@konklone) to help people test their HTTPS certificates for weak signature algorithms. SHA-1 is the current standard, but is too weak. People should use a form of SHA-2.</p>
  <h3><a href="http://agoodlongread.com/">A Good Long Read</a></h3>
  <p>The Gutenberg project is brilliant for digitizing text of classic literature. The presentation of the text leaves a lot to be desired however. This is my attempt to make the books a joy to read, as they should be.</p>
  <h3><a href="https://github.com/jonnybarnes/unicode-tools">Unicode Tools</a></h3>
  <p>Currently this composer-enabled library only does one thing. It takes a specified Unicode codepoint and outputs the UTF-8 encoded character. More to come.</p>
  <h3><a href="https://github.com/jonnybarnes/posse">POSSE</a></h3>
  <p>A PHP library to help syndicate notes to various social silos. An emphasis is placed on Twitter compatability due to its popularity and the character restrictions it places on tweets.</p>
</div>
@stop
