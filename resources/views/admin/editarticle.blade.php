@extends('master')

@section('title')
Edit Article « Admin CP
@stop

@section('content')
<form action="/admin/blog/edit/{{ $id }}" method="post" accept-charset="utf-8">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
<label for="title">Title (URL):</label>
<br>
<input type="text" name="title" id="title" value="{!! $post['0']['title'] !!}">
<br>
<input type="url" name="url" id="url" value="{!! $post['0']['url'] !!}">
<br>
<label for="main">Main:</label>
<br>
<textarea name="main" id="main">{{ $post['0']['main'] }}</textarea>
<br>
<label for="published">Published:</label><input type="checkbox" name="published" value="1"@if($post['0']['published'] == '1') checked="checked"@endif>
<br>
<input type="submit" name="save" value="Save">
</form>
<h2>Preview</h2>
@stop

@section('scripts')
@parent
<script src="/assets/js/libs/Markdown.Converter.20150827.js"></script>
<script src="/assets/js/libs/Markdown.Sanitizer.20150827.js"></script>
<script>
(function() {
  // When using more than one `textarea` on your page, change the following line to match the one you’re after
  var textarea = document.getElementsByTagName('textarea')[0];
  var section = document.getElementsByTagName('section')[0];
  var preview = document.createElement('div');
  var convert = new Markdown.getSanitizingConverter().makeHtml;
  function update() {
    preview.innerHTML = convert(textarea.value);
  }
  // Continue only if the `textarea` is found
  if (textarea) {
    preview.id = 'preview';
    // Insert the preview `div` at end of document
    section.appendChild(preview);
    textarea.oninput = function() {
      textarea.onkeyup = null;
      update();
    }
    textarea.onkeyup = update;
    // Trigger the `onkeyup` event
    textarea.onkeyup.call(textarea);
  } else {
  	console.log('no textarea found');
  }
}())
</script>
@stop
