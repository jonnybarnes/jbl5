@extends('master')

@section('title')
Note Successfully Edited « Admin CP
@stop

@section('content')
<p>Successfully edited note with id: {{ $id }}.</p>
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
