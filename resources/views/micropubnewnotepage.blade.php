@extends('master')

@section('title')
New Note « Jonny Barnes
@stop

@section('content')
<p>This is my UI for posting new notes, hopefully you’ll soon be able to use this if your site supports the micropub API.</p>
@if(!$authed)
@if($error) <p>{{ $error }}</p> @endif
<form action="/beginauth" method="post" id="login">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset>
    <legend>IndieAuth</legend>
    <label for="indie_auth_url" accesskey="a">Web Address: </label><input type="text" name="me" id="indie_auth_url" placeholder="yourdomain.com">
    <label for="kludge"></label><input type="submit" value="Sign in">
  </fieldset>
</form>
@else
<p>You are authenticated as <code>{{ $url }}</code>, <a href="/logout">log out</a>.</p>
@endif
<form action="/notes/new" method="post" enctype="multipart/form-data" accept-charset="utf-8" id="newnote">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset class="note-ui">
  	<legend>New Note</legend>
    <label for="in-reply-to" accesskey="r">Reply-to: </label><input type="text" name="in-reply-to" id="in-reply-to" placeholder="in-reply-to-1 in-reply-to-2 …">
    <label for="note" accesskey="n">Note: </label><textarea name="note" id="note" placeholder="Note"></textarea>
    <label for="webmentions" accesskey="w">Send webmentions: </label><input type="checkbox" name="webmentions" id="webmentions" checked="checked"><br>
    <label for="twitter" accesskey="t">Twitter: </label><input type="checkbox" name="twitter" id="twitter" checked="checked"><br>
    <label for="photo" accesskey="p">Photo: </label><input type="file" accept="image/*" value="Upload" name="photo" id="photo">
    <label for="locate" accesskey="l"></label><input type="button" name="locate" id="locate" value="Locate">
    <label for="kludge"></label><input type="submit" value="Submit">
  </fieldset>
</form>
@stop

@section('scripts')
<script src="/assets/js/maps.js"></script>
<script src="/assets/js/store2.min.js"></script>
<script src="/assets/js/alertify.min.js"></script>
<script src="/assets/js/form-save.js"></script>
<link rel="stylesheet" href="/assets/css/alertify.min.css">
<link rel="stylesheet" href="/assets/css/alertify.default-theme.min.css">
@stop
