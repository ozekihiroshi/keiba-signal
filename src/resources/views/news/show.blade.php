@extends('layouts.app')

@section('title', $ingest->title)

@section('content')
@php
  use App\Support\Html;
  $body = Html::sanitize_basic($ingest->summary_raw ?? '', optional($ingest->source)->base_url);
@endphp
<div class="container mx-auto px-4 py-8">
  <article class="prose max-w-none">
    <h1>{{ $ingest->title }}</h1>

    @if($ingest->image_url)
      <img src="{{ $ingest->image_url }}" alt="" class="w-full rounded-lg mb-4"/>
    @endif

    <p class="text-sm text-gray-500">
      {{ optional($ingest->published_at)->format('Y-m-d H:i') }}
      · Source: {{ optional($ingest->source)->name }}
      · <a href="{{ $ingest->url }}" target="_blank" rel="noopener">Original</a>
    </p>

    @if($ingest->summary)
      <p>{{ $ingest->summary }}</p>
    @elseif($body)
      {!! $body !!}
    @endif
  </article>

  <div class="mt-8">
    <a href="{{ route('news.index') }}" class="text-blue-600">&larr; Back to News</a>
  </div>
</div>
@endsection
