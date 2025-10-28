<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Diag\SessionDiagController;

Route::middleware(['web'])->prefix('_diag')->group(function () {
    Route::get('/session', [SessionDiagController::class, 'show'])->name('diag.session');
});

