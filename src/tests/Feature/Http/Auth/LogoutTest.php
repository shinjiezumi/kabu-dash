<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログアウトするとセッションが破棄されトップページへ遷移すること(): void
    {
        $response = $this->actingAs(User::factory()->create())->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_ログアウト後はダッシュボードにアクセスできないこと(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout');

        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_未認証でログアウトするとログイン画面へリダイレクトされること(): void
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
    }
}
