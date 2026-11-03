<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

// 偽の Host ヘッダーを、別ホストの絶対 URL へのリクエストで再現する
final class MailLinkHostTest extends TestCase
{
    use RefreshDatabase;

    private const FAKE_ROOT = 'http://evil.example';

    public function test_再設定メールのリンクはリクエストのホストに影響されないこと(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post(self::FAKE_ROOT.'/forgot-password', ['email' => 'user@example.com']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            return $this->hasApplicationHost($notification->toMail($user)->actionUrl);
        });
    }

    public function test_登録時の確認メールのリンクはリクエストのホストに影響されないこと(): void
    {
        Notification::fake();

        $this->post(self::FAKE_ROOT.'/register', [
            'name' => '山田太郎',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::sole();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
            return $this->hasApplicationHost($notification->toMail($user)->actionUrl);
        });
    }

    public function test_再送信した確認メールのリンクはリクエストのホストに影響されないこと(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(self::FAKE_ROOT.'/email/verification-notification');

        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
            return $this->hasApplicationHost($notification->toMail($user)->actionUrl);
        });
    }

    private function hasApplicationHost(string $url): bool
    {
        return parse_url($url, PHP_URL_HOST) === parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
