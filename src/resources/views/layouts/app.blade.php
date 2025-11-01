<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>

  {{-- OGP --}}
  <meta property="og:site_name" content="{{ config('app.name') }}">
  <meta property="og:title" content="@yield('title', config('app.name'))">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ secure_url(request()->path()) }}">
  <meta property="og:image" content="{{ asset('img/ogp/default.jpg') }}">

  {{-- Tailwind（簡易表示用） --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
  </style>
</head>
<body class="antialiased bg-gray-50 text-gray-900">
  <header class="border-b bg-white">
    <div class="container mx-auto px-4 py-4 flex items-center justify-between">
      <a href="{{ url('/') }}" class="font-bold">{{ config('app.name') }}</a>
      <nav class="space-x-4 text-sm">
        <a href="{{ route('news.index') }}" class="text-gray-700 hover:underline">News</a>
      </nav>
    </div>
  </header>

  <main>
    @yield('content')
  </main>

  <footer class="mt-12 border-t">
    <div class="container mx-auto px-4 py-6 text-xs text-gray-500">
      © {{ date('Y') }} {{ config('app.name') }}
    </div>
  </footer>
</body>
</html>
