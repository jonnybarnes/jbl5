@extends('master')

@section('title')
Articles « Jonny Barnes
@stop

@section('content')
@foreach ($data as $article)
@if($article['url'] != '')<article class="link h-entry">@else<article class="h-entry">@endif
<header>
<h1 class="p-name">
<a href="@if($article['url'] == ''){{ App\Http\Controllers\ArticlesController::createLink($article['date_time'], $article['titleurl']) }}@else{{ $article['url'] }}@endif">{{ $article['title'] }}</a>
</h1>
<span class="post-info">Posted <time class="dt-published" title="{{ $article['tooltip_time'] }}" datetime="{{ $article['w3c_time'] }}">{{ $article['human_time'] }}</time> - <a title="Permalink" href="{{ App\Http\Controllers\ArticlesController::createLink($article['date_time'], $article['titleurl']) }}"><span class="permalink"><?php echo html_entity_decode('&infin;'); ?></span></a></span>
</header>
<div class="e-content">
{!! $article['main'] !!}
</div>
</article>
@endforeach
{!! $data->render() !!}
@stop
