@extends('layouts.auth')

@section('title', 'メールアドレスの確認')

@section('content')
    <p class="mb-4 text-sm">メールアドレスの確認が必要です。確認メール内のリンクを開いてください。メールが届いていない場合やリンクの期限が切れた場合は、再送信してください。</p>

    @if (session('status') === \Laravel\Fortify\Fortify::VERIFICATION_LINK_SENT)
        <p class="mb-4 rounded bg-green-50 px-3 py-2 text-sm text-green-700">新しい確認メールを送信しました。</p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
        @csrf

        <button type="submit" class="w-full rounded bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-700">確認メールを再送信</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="w-full rounded border border-gray-300 px-4 py-2 font-medium hover:bg-gray-100">ログアウト</button>
    </form>
@endsection
