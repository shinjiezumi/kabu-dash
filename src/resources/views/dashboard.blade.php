{{-- 暫定プレースホルダ。ダッシュボード機能の実装時に置き換える --}}
@extends('layouts.auth')

@section('title', 'ダッシュボード')

@section('content')
    <p class="mb-6">{{ auth()->user()->name }} さんとしてログイン中です。</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="w-full rounded border border-gray-300 px-4 py-2 font-medium hover:bg-gray-100">ログアウト</button>
    </form>
@endsection