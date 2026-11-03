<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::loginView(fn () => view('auth.login'));

        // レート制限はバリデーションより先に評価されるため、未入力や配列の入力でも落ちないようにする
        RateLimiter::for('login', function (Request $request): Limit {
            $username = $request->input(Fortify::username());
            $email = is_string($username) ? Str::lower($username) : '';

            return Limit::perMinute(5)->by(Str::transliterate($email.'|'.$request->ip()));
        });
    }
}
