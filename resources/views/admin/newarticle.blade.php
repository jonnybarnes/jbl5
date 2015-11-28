@extends('master')

@section('title')
New Article « Admin CP
@stop

@section('content')
@if(isset($message))<p class="error">{{ $message }}</p>@endif
<form action="/admin/blog/new" method="post" accept-charset="utf-8" id="newarticle">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
<label for="title">Title (URL):</label>
<br>
<input type="text" name="title" id="title" value="{{ Input::old('title') }}" placeholder="Title here">
<br>
<input type="text" name="url" id="url" value="{{Input::old('url') }}" placeholder="Article URL"></textarea>
<br>
<label for="main">Main:</label>
<br>
<textarea name="main" id="main" placeholder="Article here">{{ Input::old('main') }}</textarea>
<br>
<label for="published">Published:</label><input type="checkbox" name="published" id="published" value="1">
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
<script src="{{ elixir('assets/js/store2.min.js') }}"></script>
<script src="{{ elixir('assets/js/alertify.js') }}"></script>
<script src="{{ elixir('assets/js/form-save.js') }}"></script>

<link rel="stylesheet" href="{{ elixir('assets/css/alertify.css') }}">
@stop
