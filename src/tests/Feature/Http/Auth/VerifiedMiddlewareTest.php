<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VerifiedMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_未確認ユーザーはダッシュボードから確認案内画面へリダイレクトされること(): void
    {
        $response = $this->actingAs(User::factory()->unverified()->create())->get('/dashboard');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_確認済みユーザーはダッシュボードにアクセスできること(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard');
    }

    public function test_未認証ではダッシュボードからログイン画面へリダイレクトされること(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_未確認ユーザーでもログアウトできること(): void
    {
        $response = $this->actingAs(User::factory()->unverified()->create())->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_未確認ユーザーでもログインでき確認案内画面へ遷移すること(): void
    {
        User::factory()->unverified()->create(['email' => 'user@example.com']);

        $response = $this->followingRedirects()->post('/login', ['email' => 'user@example.com', 'password' => 'password']);

        $response->assertOk();
        $response->assertViewIs('auth.verify-email');
        $this->assertAuthenticated();
    }
}
