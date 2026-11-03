<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class DisabledFeaturesTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function スコープ外のルート名(): array
    {
        return [
            '2要素認証' => ['two-factor.login.store'],
            'パスキー' => ['passkey.login'],
            'プロフィール更新' => ['user-profile-information.update'],
            'ログイン中のパスワード変更' => ['user-password.update'],
        ];
    }

    #[DataProvider('スコープ外のルート名')]
    public function test_スコープ外の機能のルートが登録されていないこと(string $routeName): void
    {
        $this->assertFalse(Route::has($routeName));
    }
}
