@extends('master')

@section('title')
New Note « Admin CP
@stop

@section('content')
@if (count($errors) > 0)
  <div class="errors">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
@include('templates.new-note-form', [
  'micropub' => false,
  'action' => '/admin/note/new',
  'id' => 'newnote-admin'
])
@stop

@section('scripts')
<script src="/assets/js/libs/mapbox.v2.2.1.js"></script>
<script src="{{ elixir('assets/js/newnote.js') }}"></script>
<script src="/assets/js/libs/store2.v2.1.6.min.js"></script>
<script src="/assets/js/libs/alertify.v0.10.2.min.js"></script>
<script src="{{ elixir('assets/js/form-save.js') }}"></script>

<link rel="stylesheet" href="/assets/css/alertify.v0.10.2.min.css">
<link rel="stylesheet" href="/assets/css/alertify.default-theme.v0.10.2.min.css">
@stop
