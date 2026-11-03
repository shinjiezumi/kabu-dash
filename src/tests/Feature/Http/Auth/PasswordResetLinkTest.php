<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class PasswordResetLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_再設定リンクの送信画面が表示されること(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk();
        $response->assertViewIs('auth.forgot-password');
    }

    public function test_ログイン画面に再設定リンクの送信画面へのリンクがあること(): void
    {
        $this->get('/login')->assertSee(route('password.request'));
    }

    public function test_認証済みユーザーは送信画面からダッシュボードへリダイレクトされること(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/forgot-password');

        $response->assertRedirect('/dashboard');
    }

    public function test_登録済みのメールアドレスには再設定リンクが送信されること(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->post('/forgot-password', ['email' => 'user@example.com']);

        $response->assertSessionHas('status', trans('passwords.sent'));
        $response->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'user@example.com']);
    }

    public function test_大文字混じりのメールアドレスでも再設定リンクが送信されること(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post('/forgot-password', ['email' => 'USER@Example.com']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_未登録のメールアドレスでも登録済みと同じ応答になり通知は送信されないこと(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', ['email' => 'unknown@example.com']);

        $response->assertSessionHas('status', trans('passwords.sent'));
        $response->assertSessionHasNoErrors();
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_短時間に再送しても同じ応答になり通知は増えないこと(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'user@example.com']);

        $this->post('/forgot-password', ['email' => 'user@example.com']);
        $response = $this->post('/forgot-password', ['email' => 'user@example.com']);

        $response->assertSessionHas('status', trans('passwords.sent'));
        $response->assertSessionHasNoErrors();
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_送信後は元の画面に戻り完了メッセージが表示されること(): void
    {
        Notification::fake();

        $response = $this->from('/forgot-password')
            ->followingRedirects()
            ->post('/forgot-password', ['email' => 'unknown@example.com']);

        $response->assertOk();
        $response->assertViewIs('auth.forgot-password');
        $response->assertSee(trans('passwords.sent'));
    }

    public function test_メールアドレスの形式が不正な場合はバリデーションエラーになること(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', ['email' => 'invalid']);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_メールアドレスが未入力の場合はバリデーションエラーになること(): void
    {
        $response = $this->post('/forgot-password', []);

        $response->assertSessionHasErrors('email');
    }

    public function test_配列のメールアドレスを送った後も送信画面を表示できること(): void
    {
        $response = $this->withSession(['_old_input' => ['email' => ['user@example.com']]])
            ->get('/forgot-password');

        $response->assertOk();
    }

    public function test_同一接続元から5回を超えて送信すると429になること(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => "unknown{$i}@example.com"])->assertSessionHasNoErrors();
        }

        $this->post('/forgot-password', ['email' => 'user@example.com'])->assertTooManyRequests();
        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_送信画面の表示はレート制限の対象外であること(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->get('/forgot-password')->assertOk();
        }
    }
}
