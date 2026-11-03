<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::createUsersUsing(CreateNewUser::class);

        // レート制限はバリデーションより先に評価されるため、未入力や配列の入力でも落ちないようにする
        RateLimiter::for('login', function (Request $request): Limit {
            $username = $request->input(Fortify::username());

            // PostgreSQL は NUL 以降を切り捨てて照合する。NUL や不正なバイト列をキーに含めると、
            // 同じアカウントに対してキーだけを変えて制限を回避できるため、キーから除外する
            $isSafe = is_string($username)
                && mb_check_encoding($username, 'UTF-8')
                && ! str_contains($username, "\0");
            $email = $isSafe ? Str::lower($username) : '';

            return Limit::perMinute(5)->by(Str::transliterate($email.'|'.$request->ip()));
        });

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        // Fortify には登録ルート用のレート制限設定がないため、ルートの読み込み後に追加する
        // ルートキャッシュの利用時はこの時点でルートが未読み込みだが、キャッシュにミドルウェアが含まれるので null を許容する
        $this->app->booted(function (): void {
            $routes = Route::getRoutes();
            $routes->refreshNameLookups();
            $routes->getByName('register.store')?->middleware('throttle:register');
        });
    }
}
