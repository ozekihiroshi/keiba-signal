@extends('layouts.app')

@section('title', 'News')

@section('content')
<div class="container mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-6">News</h1>

  @if($ingests->isEmpty())
    <p class="text-gray-600">まだ公開記事がありません。</p>
  @else
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      @foreach($ingests as $ing)
        <a class="block border rounded-lg p-4 hover:shadow transition" href="{{ route('news.show', $ing) }}">
          <x-ogp.article-card :title="$ing->title" :image="$ing->image_url" :summary="$ing->summary ?? $ing->summary_raw" :url="$ing->url" />
          <div class="mt-3 text-sm text-gray-500">
            {{ optional($ing->published_at)->format('Y-m-d H:i') }}
          </div>
        </a>
      @endforeach
    </div>

    <div class="mt-8">
      {{ $ingests->links() }}
    </div>
  @endif
</div>
@endsection
