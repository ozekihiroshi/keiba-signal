<?php

use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return view("welcome");
});

Route::get("/health", fn() => "ok");
Route::match(["GET","POST"], "/login", fn() => redirect()->route("filament.admin.auth.login"))->name("login");
Route::post('/admin/login', fn()=>redirect('/admin/login'));

Route::get("/__sess_put", function () { session(["_ts"=>time()]); return "put"; });
Route::get("/__sess_get", function () { return "ts=".session("_ts","none"); });

// added by patch_include_diag_routes.sh
require base_path('routes/diag.php');
