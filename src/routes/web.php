<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 認証の動作確認用の暫定ルート。ダッシュボード機能の実装時に置き換える
Route::middleware('auth')->get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');
