@extends('master')

@section('title')
{{ $place->name }} « Places « Jonny Barnes
@stop

@section('content')
<h1>{{ $place->name }}</h1>
<p>{{ $place->description or 'No description'}}</p>
@stop
