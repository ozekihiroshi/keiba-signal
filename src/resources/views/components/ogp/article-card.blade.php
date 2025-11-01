@props(['title','summary'=>null,'image'=>null,'url'=>null])

@php
  // 画像フォールバック：JRA公式ならサイト既定のOGPへ、それ以外はローカル既定
  $host = $url ? parse_url($url, PHP_URL_HOST) : null;
  $fallback = ($host && str_contains($host, 'japanracing.jp'))
    ? 'https://japanracing.jp/en/common/img/ogp.jpg'
    : asset('img/ogp/default.jpg');

  $imgSrc = $image ?: $fallback;
@endphp

<div class="flex flex-col">
  <div class="relative w-full h-40 bg-gray-100 rounded-md overflow-hidden">
    <img src="{{ $imgSrc }}" alt="" class="w-full h-full object-cover">
  </div>
  <div class="mt-3">
    <h3 class="font-semibold leading-snug">{{ $title }}</h3>
    @if($summary)
      <p class="text-sm text-gray-600 mt-1 line-clamp-3">{{ $summary }}</p>
    @endif
    @if($url)
      <a href="{{ $url }}" target="_blank" rel="noopener" class="inline-block text-sm text-blue-600 mt-2">Original</a>
    @endif
  </div>
</div>
