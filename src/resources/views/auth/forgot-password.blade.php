@extends('layouts.auth')

@section('title', 'パスワードの再設定')

@section('content')
    <p class="mb-4 text-sm">登録したメールアドレスを入力してください。パスワード再設定用のリンクを送信します。</p>

    @if (session('status'))
        <p class="mb-4 rounded bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}" required autofocus autocomplete="username" class="w-full rounded border border-gray-300 px-3 py-2">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full rounded bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-700">再設定リンクを送信</button>
    </form>

    <p class="mt-4 text-center text-sm">
        <a href="{{ route('login') }}" class="underline">ログインに戻る</a>
    </p>
@endsection
