<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_登録時に確認メールが送信されること(): void
    {
        Notification::fake();

        $this->post('/register', $this->registrationInput());

        $user = User::sole();
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_登録時に確認日時を送っても確認済みにならないこと(): void
    {
        $this->post('/register', [...$this->registrationInput(), 'email_verified_at' => now()->toDateTimeString()]);

        $this->assertFalse(User::sole()->hasVerifiedEmail());
    }

    public function test_登録直後はログイン状態のまま確認案内画面へ誘導されること(): void
    {
        $response = $this->followingRedirects()->post('/register', $this->registrationInput());

        $response->assertOk();
        $response->assertViewIs('auth.verify-email');
        $this->assertAuthenticated();
    }

    public function test_未確認ユーザーに確認案内画面が表示されること(): void
    {
        $response = $this->actingAs(User::factory()->unverified()->create())->get('/email/verify');

        $response->assertOk();
        $response->assertViewIs('auth.verify-email');
        $response->assertSee(route('verification.send'));
        $response->assertSee(route('logout'));
    }

    public function test_再送信していなければ確認案内画面に送信完了の文言が出ないこと(): void
    {
        $response = $this->actingAs(User::factory()->unverified()->create())->get('/email/verify');

        $response->assertSee('メールアドレスの確認が必要です。');
        $response->assertDontSee('送信しました');
    }

    public function test_確認済みユーザーは確認案内画面からダッシュボードへリダイレクトされること(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/email/verify');

        $response->assertRedirect('/dashboard');
    }

    public function test_未認証では確認案内画面からログイン画面へリダイレクトされること(): void
    {
        $this->get('/email/verify')->assertRedirect('/login');
    }

    public function test_有効な署名付きリンクでメールアドレスが確認されること(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get($this->verificationUrl($user));

        $response->assertRedirect('/dashboard?verified=1');
        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_確認後はダッシュボードにアクセスできること(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get($this->verificationUrl($user));

        $this->get('/dashboard')->assertOk();
    }

    public function test_確認済みユーザーが再度リンクを開いても確認済み日時とイベントが重複しないこと(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();
        $url = $this->verificationUrl($user);

        $this->actingAs($user)->get($url);
        $verifiedAt = $user->refresh()->email_verified_at;
        $this->travel(5)->minutes();
        $response = $this->get($url);

        $response->assertRedirect('/dashboard?verified=1');
        $this->assertEquals($verifiedAt, $user->refresh()->email_verified_at);
        Event::assertDispatchedTimes(Verified::class, 1);
    }

    public function test_署名が改ざんされたリンクでは確認できないこと(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get($this->verificationUrl($user).'&tampered=1');

        $response->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_署名のないリンクでは確認できないこと(): void
    {
        $user = User::factory()->unverified()->create();
        $url = route('verification.verify', ['id' => $user->getKey(), 'hash' => sha1($user->email)]);

        $response = $this->actingAs($user)->get($url);

        $response->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_有効期限の60分を過ぎたリンクでは確認できないこと(): void
    {
        $user = User::factory()->unverified()->create();
        $url = $this->verificationUrl($user);

        $this->travel(61)->minutes();
        $response = $this->actingAs($user)->get($url);

        $response->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_メールアドレスのハッシュが一致しないリンクでは確認できないこと(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get($this->verificationUrl($user, sha1('other@example.com')));

        $response->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_別のユーザー宛てのリンクでは確認できないこと(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get($this->verificationUrl($other));

        $response->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
        $this->assertFalse($other->refresh()->hasVerifiedEmail());
    }

    public function test_未認証でリンクを開くとログイン画面へリダイレクトされ確認されないこと(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->get($this->verificationUrl($user));

        $response->assertRedirect('/login');
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_未ログインでリンクを開いてもログイン後に確認が完了すること(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'user@example.com']);

        $this->get($this->verificationUrl($user))->assertRedirect('/login');
        $response = $this->followingRedirects()->post('/login', ['email' => 'user@example.com', 'password' => 'password']);

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_メール内のリンクで確認できること(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get((new VerifyEmail)->toMail($user)->actionUrl);

        $response->assertRedirect('/dashboard?verified=1');
        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_メール内のリンクは60分を過ぎると使えないこと(): void
    {
        $user = User::factory()->unverified()->create();
        $url = (new VerifyEmail)->toMail($user)->actionUrl;

        $this->travel(61)->minutes();
        $response = $this->actingAs($user)->get($url);

        $response->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_パスワードを再設定しても確認済みにならないこと(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'user@example.com']);

        $this->post('/reset-password', [
            'token' => Password::createToken($user),
            'email' => 'user@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertRedirect('/login');

        $this->assertFalse($user->refresh()->hasVerifiedEmail());

        $response = $this->followingRedirects()->post('/login', ['email' => 'user@example.com', 'password' => 'new-password123']);

        $response->assertOk();
        $response->assertViewIs('auth.verify-email');
    }

    public function test_署名が不正なリンクの試行もレート制限の対象であること(): void
    {
        $user = User::factory()->unverified()->create();
        $url = $this->verificationUrl($user).'&tampered=1';

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }

        $this->actingAs($user)->get($url)->assertTooManyRequests();
    }

    public function test_リンク確認のレート制限はユーザーごとに数えること(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $url = $this->verificationUrl($user).'&tampered=1';

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->get($url);
        }

        $this->actingAs($other)->get($this->verificationUrl($other))->assertRedirect('/dashboard?verified=1');
    }

    public function test_再送信を使い切ってもリンクで確認できること(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post('/email/verification-notification')->assertSessionHas('status');
        }

        $this->actingAs($user)->post('/email/verification-notification')->assertTooManyRequests();

        $this->actingAs($user)->get($this->verificationUrl($user))->assertRedirect('/dashboard?verified=1');
        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    private function verificationUrl(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => $hash ?? sha1($user->getEmailForVerification()),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function registrationInput(): array
    {
        return [
            'name' => '山田太郎',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }
}
