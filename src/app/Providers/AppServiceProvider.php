<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // メール内のリンクが Host ヘッダーの値で書き換えられないよう、APP_URL に固定する
        URL::forceRootUrl(config('app.url'));
    }
}
