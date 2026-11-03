@extends('layouts.auth')

@section('title', 'ログイン')

@section('content')
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}" required autofocus autocomplete="username" class="w-full rounded border border-gray-300 px-3 py-2">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">パスワード</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="w-full rounded border border-gray-300 px-3 py-2">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full rounded bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-700">ログイン</button>
    </form>

    <p class="mt-4 text-center text-sm">
        <a href="{{ route('register') }}" class="underline">ユーザー登録はこちら</a>
    </p>
@endsection