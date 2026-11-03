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

    public function test_レート制限は別の接続元に影響しないこと(): void
    {
        $credentials = ['email' => 'user@example.com', 'password' => 'wrong-password'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $credentials);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])
            ->post('/login', $credentials)
            ->assertSessionHasErrors('email');
    }

    public function test_ヌル文字を付けてもレート制限を回避できないこと(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => "user@example.com\0{$i}", 'password' => 'wrong-password'])
                ->assertRedirect();
        }

        $this->post('/login', ['email' => "user@example.com\0last", 'password' => 'wrong-password'])
            ->assertTooManyRequests();
    }

    public function test_不正なバイト列を付けてもレート制限を回避できないこと(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => "user@example.com\xff{$i}", 'password' => 'wrong-password'])
                ->assertRedirect();
        }

        $this->post('/login', ['email' => "user@example.com\xfflast", 'password' => 'wrong-password'])
            ->assertTooManyRequests();
    }

    public function test_配列のメールアドレスを送った後もログイン画面を表示できること(): void
    {
        $response = $this->from('/login')
            ->followingRedirects()
            ->post('/login', ['email' => ['user@example.com'], 'password' => 'password']);

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_入力エラー後はメールアドレスの入力値が画面に残ること(): void
    {
        $response = $this->from('/login')
            ->followingRedirects()
            ->post('/login', ['email' => 'user@example.com', 'password' => 'wrong-password']);

        $response->assertOk();
        $response->assertSee('value="user@example.com"', false);
    }
}
