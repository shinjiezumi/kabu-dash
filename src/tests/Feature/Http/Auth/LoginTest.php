<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン画面が表示されること(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_認証済みユーザーはログイン画面からダッシュボードへリダイレクトされること(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/login');

        $response->assertRedirect('/dashboard');
    }

    public function test_正しい認証情報でログインするとダッシュボードへ遷移すること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->post('/login', ['email' => 'user@example.com', 'password' => 'password']);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_メールアドレスが大文字混じりでもログインできること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->post('/login', ['email' => 'USER@Example.com', 'password' => 'password']);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_パスワードが誤っている場合はログインできないこと(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong-password']);

        $response->assertSessionHasErrors(['email' => __('auth.failed')]);
        $this->assertGuest();
    }

    public function test_未登録のメールアドレスではパスワード誤りと同じエラーになること(): void
    {
        $response = $this->post('/login', ['email' => 'unknown@example.com', 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => __('auth.failed')]);
        $this->assertGuest();
    }

    public function test_メールアドレスが未入力の場合はバリデーションエラーになること(): void
    {
        $response = $this->post('/login', ['password' => 'password']);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_メールアドレスが配列の場合はバリデーションエラーになること(): void
    {
        $response = $this->post('/login', ['email' => ['user@example.com'], 'password' => 'password']);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_同一メールアドレスと接続元で5回を超えて試行すると429になること(): void
    {
        $credentials = ['email' => 'user@example.com', 'password' => 'wrong-password'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $credentials)->assertSessionHasErrors('email');
        }

        $this->post('/login', $credentials)->assertTooManyRequests();
    }

    public function test_大文字小文字を変えてもレート制限を回避できないこと(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => 'USER@example.com', 'password' => 'wrong-password'])
            ->assertTooManyRequests();
    }

    public function test_レート制限は別のメールアドレスに影響しないこと(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => 'other@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
    }
}
