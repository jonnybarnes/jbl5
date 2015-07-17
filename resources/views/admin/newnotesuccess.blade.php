@extends('master')

@section('title')
New Note Success « Admin CP
@stop

@section('content')
<p>Successfully created note with id: {{ $id }}. {{ $shorturl }}</p>
@if($webmentions)
<h2>Attempted Webmentions</h2>
<p>Success:</p>
@if($webmentions[0])
<ul>
  @foreach($webmentions[0] as $url)
  <li>{{ $url }}</li>
  @endforeach
</ul>
@endif
<p>Failure</p>
@if($webmentions[1])
<ul>
  @foreach($webmentions[1] as $url)
  <li>{{ $url }}</li>
  @endforeach
</ul>
@endif
@endif
@stop
