<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') - {{ config('app.name') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="flex min-h-screen items-center justify-center bg-gray-100 p-4 text-gray-900">
        <main class="w-full max-w-md rounded-lg bg-white p-8 shadow">
            <h1 class="mb-6 text-2xl font-semibold">@yield('title')</h1>

            @yield('content')
        </main>
    </body>
</html>