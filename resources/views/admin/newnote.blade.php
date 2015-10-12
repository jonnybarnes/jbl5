@extends('master')

@section('title')
New Note « Admin CP
@stop

@section('content')
@include('templates.new-note-form', [
  'micropub' => false,
  'action' => '/admin/note/new',
  'id' => 'newnote-admin'
])
@stop

@section('scripts')
<script src="{{ elixir('assets/js/maps.js') }}"></script>
<script src="{{ elixir('assets/js/form-save.js') }}"></script>
<script src="/assets/js/libs/store2.v2.1.6.min.js"></script>
<script src="/assets/js/libs/alertify.v0.10.2.min.js"></script>

<link rel="stylesheet" href="/assets/css/alertify.v0.10.2.min.css">
<link rel="stylesheet" href="/assets/css/alertify.default-theme.v0.10.2.min.css">
@stop
