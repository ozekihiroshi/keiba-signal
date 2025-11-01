@extends('layouts.app')

@section('title', 'Today\'s Topics')

@section('content')
<div class="container mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-6">Today’s Topics (JRA / RSS)</h1>
  <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
    @forelse(($ingests ?? []) as $ing)
      <a class="block border rounded-lg p-4 hover:shadow transition" href="{{ route('ingests.show', $ing) }}">
        <x-ogp.article-card :title="$ing->title" :image="$ing->image_url" :summary="$ing->summary ?? $ing->summary_raw" :url="$ing->url" />
        <div class="mt-3 text-sm text-gray-500">
          {{ optional($ing->published_at)->format('Y-m-d H:i') }}
        </div>
      </a>
    @empty
      <p>No articles yet.</p>
    @endforelse
  </div>
</div>
@endsection
