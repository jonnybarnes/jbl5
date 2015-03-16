@extends('master')

@section('title')
New Note « Admin CP
@stop

@section('content')
<form action="/admin/note/new" method="post" enctype="multipart/form-data" accept-charset="utf-8" id="newnote-admin">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset class="note-ui">
    <legend>New Note</legend>
    <label for="in-reply-to" accesskey="r">Reply-to: </label><input type="text" name="in-reply-to" id="in-reply-to" placeholder="in-reply-to-1 in-reply-to-2 …"><br>
    <label for="content" accesskey="n">Note: </label><textarea name="content" id="content" placeholder="Note"></textarea><br>
    <label for="webmentions" accesskey="w">Send webmentions: </label><input type="checkbox" name="webmentions" id="webmentions" checked="checked"><br>
    <label for="twitter" accesskey="t">Twitter: </label><input type="checkbox" name="twitter" id="twitter"><br>
    <label for="photo" accesskey="p">Photo: </label><input type="file" accept="image/*" value="Upload" name="photo" id="photo"><br>
    <label for="locate" accesskey="l"></label><input type="button" name="locate" id="locate" value="Locate"><br>
    <label for="kludge"></label><input type="submit" value="Submit"><br>
    <div class="geo-status"></div>
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
