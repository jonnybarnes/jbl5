@extends('master')

@section('title')
Edit Note « Admin CP
@stop

@section('content')
<form action="/admin/note/edit/{{ $id }}" method="post" accept-charset="utf-8">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <fieldset class="note-ui">
    <legend>Edit Note</legend>
    <label for="reply-to" accesskey="r">Reply-to: </label><input type="text" name="reply-to" id="reply-to" placeholder="in-reply-to-1 in-reply-to-2 …" tabindex="1" value="{{ $note->reply_to }}"><br>
    <label for="note" accesskey="n">Note: </label><textarea name="note" id="note" placeholder="Note" tabindex="2">{{ $note->note }}</textarea><br>
    <label for="webmentions" accesskey="w">Send webmentions: </label><input type="checkbox" name="webmentions" id="webmentions" checked="checked" tabindex="3"><br>
    <label for="kludge"></label><input type="submit" value="Submit" tabindex="6"><br>
  </fieldset>
</form>
@stop

@section('scripts')
<script src="/assets/js/maps.js"></script>
@stop
