@extends('master')

@section('title')
New Note « Jonny Barnes
@stop

@section('content')
<p>This is my UI for posting new notes, hopefully you’ll soon be able to use this if your site supports the micropub API.</p>
@if($errorMessage != false)<p class="error">{{ $errorMessage }}</p>@endif
@if(!$authed)
<form action="/beginauth" method="post" id="login">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset>
    <legend>IndieAuth</legend>
    <label for="indie_auth_url" accesskey="a">Web Address: </label><input type="text" name="me" id="indie_auth_url" placeholder="yourdomain.com">
    <label for="kludge"></label><button type="submit" name="sign-in" id="sign-in" value="Sign in">Sign in</button>
  </fieldset>
</form>
@else
<p>You are authenticated as <code>{{ $url }}</code>, <a href="/logout">log out</a>.</p>
@endif
  @include('templates.new-note-form', [
    'micropub' => true,
    'action' => '/notes/new',
    'id' => 'newnote'
  ])
@stop

@section('scripts')
<script src="/assets/js/libs/mapbox.v2.2.1.js"></script>
<script src="/assets/js/libs/store2.v2.1.6.min.js"></script>
<script src="/assets/js/libs/alertify.v0.10.2.min.js"></script>
<script src="{{ elixir('assets/js/maps.js') }}"></script>
<script src="{{ elixir('assets/js/form-save.js') }}"></script>
<script src="{{ elixir('assets/js/newnote.js') }}"></script>

<link rel="stylesheet" href="/assets/css/alertify.v0.10.2.min.css">
<link rel="stylesheet" href="/assets/css/alertify.default-theme.v0.10.2.min.css">
@stop
