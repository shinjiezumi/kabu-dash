<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class AuthenticateSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_パスワードが変更されると古いセッションはログイン画面へリダイレクトされること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $this->loginAndVisit('/dashboard');

        $this->changePasswordFromAnotherSession($user);
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_メール確認が済んでも再設定前のセッションは使えないこと(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'user@example.com']);
        $this->loginAndVisit('/email/verify');

        $this->changePasswordFromAnotherSession($user);
        $user->refresh()->markEmailAsVerified();
        Auth::forgetGuards();
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_パスワードが変わらなければセッションは維持されること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $this->loginAndVisit('/dashboard');

        Auth::forgetGuards();
        $response = $this->get('/dashboard');

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    /**
     * ログインし、セッションにパスワードのハッシュが保存されるようページを 1 回開く
     */
    private function loginAndVisit(string $uri): void
    {
        $this->post('/login', ['email' => 'user@example.com', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->get($uri)->assertOk();
    }

    /**
     * 別のセッションからの再設定を再現する。次のリクエストでユーザーを読み直すようガードも破棄する
     */
    private function changePasswordFromAnotherSession(User $user): void
    {
        (new ResetUserPassword)->reset($user->fresh(), [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        Auth::forgetGuards();
    }
}
