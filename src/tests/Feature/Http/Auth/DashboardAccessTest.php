<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証ではログイン画面へリダイレクトされること(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_認証済みならダッシュボードが表示されること(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard');
    }
}
