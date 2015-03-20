@extends('master')

@section('title')
{{ strip_tags($data['0']['title']) }} <?php echo html_entity_decode("&laquo;"); ?> Jonny Barnes
@stop

@section('content')
@if($data['0']['url'] != '')<article class="link h-entry">@else<article class="h-entry">@endif
<header>
<h1 class="p-name">
<a href="@if($data['0']['url'] == ''){{ App\Http\Controllers\ArticlesController::createLink($data['0']['date_time'], $data['0']['titleurl']) }}@else{{ $data['0']['url'] }}@endif">{{ $data['0']['title'] }}</a>
</h1>
<span class="post-info">Posted <time class="dt-published" title="{{ $data['0']['tooltip_time'] }}" datetime="{{ $data['0']['w3c_time'] }}">{{ $data['0']['human_time'] }}</time> - <a title="Permalink" href="{{ App\Http\Controllers\ArticlesController::createLink($data['0']['date_time'], $data['0']['titleurl']) }}"><span class="permalink"><?php echo html_entity_decode('&infin;'); ?></span></a></span>
</header>
<div class="e-content">
{!! $data['0']['main'] !!}
</div>
</article>
@stop
