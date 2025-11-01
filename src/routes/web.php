<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\IngestPublicController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| 既存のルーティングに影響を与えないよう、/news 配下のみを定義します。
*/

Route::get('/news', [IngestPublicController::class, 'index'])
    ->name('news.index');

Route::get('/news/{ingest}', [IngestPublicController::class, 'show'])
    ->whereNumber('ingest')
    ->name('news.show');
