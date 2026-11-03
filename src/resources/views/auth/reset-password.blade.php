@extends('layouts.auth')

@section('title', '新しいパスワードの設定')

@section('content')
    {{-- メールアドレスはリンクのクエリ、またはエラー後の入力値から取得する。配列などは無視する --}}
    @php($email = old('email', $request->query('email')))

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ is_string($email) ? $email : '' }}" required autofocus autocomplete="username" class="w-full rounded border border-gray-300 px-3 py-2">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">新しいパスワード</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="w-full rounded border border-gray-300 px-3 py-2">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">新しいパスワード（確認）</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded border border-gray-300 px-3 py-2">
        </div>

        <button type="submit" class="w-full rounded bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-700">パスワードを更新</button>
    </form>
@endsection
