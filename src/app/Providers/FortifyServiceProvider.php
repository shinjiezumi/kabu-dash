<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\ConcealedPasswordResetLinkResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Fortify が制限を設定しないルート名と、適用するレート制限の対応
     */
    private const THROTTLED_ROUTES = [
        'register.store' => 'register',
        'password.email' => 'password-reset',
        'password.update' => 'password-reset',
    ];

    public function boot(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        // 未登録のメールアドレスでも成功時と同じ応答にし、登録有無を判別できないようにする
        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, ConcealedPasswordResetLinkResponse::class);

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

        // 再送信を使い切っても確認リンクを開けるよう、ルートごとにカウントする
        RateLimiter::for('verification', fn (Request $request): Limit => Limit::perMinute(6)
            ->by($request->route()?->getName().'|'.$request->user()?->getAuthIdentifier()));

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        // 再設定リンクの送信とパスワード更新で共有する。更新側はトークンの総当たりを抑える
        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        // Fortify には登録・再設定ルート用のレート制限設定がないため、ルートの読み込み後に追加する
        // ルートキャッシュの利用時はこの時点でルートが未読み込みだが、キャッシュにミドルウェアが含まれるので null を許容する
        $this->app->booted(function (): void {
            $routes = Route::getRoutes();
            $routes->refreshNameLookups();

            foreach (self::THROTTLED_ROUTES as $routeName => $limiter) {
                $routes->getByName($routeName)?->middleware('throttle:'.$limiter);
            }
        });
    }
}
