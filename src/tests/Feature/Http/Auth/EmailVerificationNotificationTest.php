<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

final class EmailVerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_未確認ユーザーに確認メールを再送信できること(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->from('/email/verify')->post('/email/verification-notification');

        $response->assertRedirect('/email/verify');
        $response->assertSessionHas('status', Fortify::VERIFICATION_LINK_SENT);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_再送信後の確認案内画面に完了メッセージが表示されること(): void
    {
        Notification::fake();

        $response = $this->actingAs(User::factory()->unverified()->create())
            ->from('/email/verify')
            ->followingRedirects()
            ->post('/email/verification-notification');

        $response->assertOk();
        $response->assertViewIs('auth.verify-email');
        $response->assertSee('新しい確認メールを送信しました。');
    }

    public function test_確認済みユーザーが再送信するとダッシュボードへリダイレクトされ通知は送信されないこと(): void
    {
        Notification::fake();

        $response = $this->actingAs(User::factory()->create())->post('/email/verification-notification');

        $response->assertRedirect('/dashboard');
        Notification::assertNothingSent();
    }

    public function test_未認証では再送信できずログイン画面へリダイレクトされること(): void
    {
        Notification::fake();

        $response = $this->post('/email/verification-notification');

        $response->assertRedirect('/login');
        Notification::assertNothingSent();
    }

    public function test_6回を超えて再送信すると429になること(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post('/email/verification-notification')->assertSessionHas('status');
        }

        $this->actingAs($user)->post('/email/verification-notification')->assertTooManyRequests();
        Notification::assertSentTimes(VerifyEmail::class, 6);
    }

    public function test_再送信のレート制限はユーザーごとに数えること(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post('/email/verification-notification');
        }

        $this->actingAs($other)->post('/email/verification-notification')->assertSessionHas('status');
        Notification::assertSentToTimes($other, VerifyEmail::class, 1);
    }
}
