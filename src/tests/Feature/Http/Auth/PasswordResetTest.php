<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_再設定画面にトークンとメールアドレスが引き継がれること(): void
    {
        $response = $this->get('/reset-password/sample-token?email=user@example.com');

        $response->assertOk();
        $response->assertViewIs('auth.reset-password');
        $response->assertSee('name="token" value="sample-token"', false);
        $response->assertSee('value="user@example.com"', false);
    }

    public function test_クエリのメールアドレスが配列でも再設定画面を表示できること(): void
    {
        $response = $this->get('/reset-password/sample-token?email[]=user@example.com');

        $response->assertOk();
        $response->assertDontSee('value="user@example.com"', false);
    }

    public function test_認証済みユーザーは再設定画面からダッシュボードへリダイレクトされること(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/reset-password/sample-token');

        $response->assertRedirect('/dashboard');
    }

    public function test_有効なトークンでパスワードを更新できること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'password' => 'old-password123']);
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', $this->input($token));

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', trans('passwords.reset'));
        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password123', $user->refresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'user@example.com']);
        $this->assertGuest();
    }

    public function test_更新後は新しいパスワードでのみログインできること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'password' => 'old-password123']);
        $this->post('/reset-password', $this->input(Password::createToken($user)));

        $this->post('/login', ['email' => 'user@example.com', 'password' => 'old-password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'user@example.com', 'password' => 'new-password123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_更新後のログイン画面に完了メッセージが表示されること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->followingRedirects()->post('/reset-password', $this->input(Password::createToken($user)));

        $response->assertOk();
        $response->assertViewIs('auth.login');
        $response->assertSee(trans('passwords.reset'));
    }

    public function test_更新時にremember_tokenが再生成されること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $before = $user->remember_token;

        $this->post('/reset-password', $this->input(Password::createToken($user)));

        $this->assertNotSame($before, $user->refresh()->remember_token);
    }

    public function test_大文字混じりのメールアドレスでも更新できること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [...$this->input($token), 'email' => 'USER@Example.com']);

        $response->assertRedirect('/login');
        $this->assertTrue(Hash::check('new-password123', $user->refresh()->password));
    }

    public function test_使用済みのトークンでは再度更新できないこと(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);
        $this->post('/reset-password', $this->input($token));

        $response = $this->post('/reset-password', [...$this->input($token), 'password' => 'another-password123', 'password_confirmation' => 'another-password123']);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Hash::check('another-password123', $user->refresh()->password));
    }

    public function test_無効なトークンでは更新できないこと(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'password' => 'old-password123']);
        Password::createToken($user);

        $response = $this->post('/reset-password', $this->input('invalid-token'));

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password123', $user->refresh()->password));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'user@example.com']);
    }

    public function test_別のユーザーのメールアドレスではトークンが使えないこと(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $other = User::factory()->create(['email' => 'other@example.com', 'password' => 'other-password123']);
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [...$this->input($token), 'email' => 'other@example.com']);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('other-password123', $other->refresh()->password));
    }

    public function test_未登録のメールアドレスでは更新できないこと(): void
    {
        $response = $this->post('/reset-password', [...$this->input('any-token'), 'email' => 'unknown@example.com']);

        $response->assertSessionHasErrors('email');
    }

    public function test_有効期限の60分を過ぎたトークンでは更新できないこと(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'password' => 'old-password123']);
        $token = Password::createToken($user);

        $this->travel(61)->minutes();
        $response = $this->post('/reset-password', $this->input($token));

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password123', $user->refresh()->password));
    }

    public function test_有効期限内のトークンなら更新できること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);

        $this->travel(59)->minutes();
        $response = $this->post('/reset-password', $this->input($token));

        $response->assertRedirect('/login');
        $this->assertTrue(Hash::check('new-password123', $user->refresh()->password));
    }

    public function test_パスワードが一致しない場合は更新できずトークンが残ること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'password' => 'old-password123']);
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [...$this->input($token), 'password_confirmation' => 'different-password']);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('old-password123', $user->refresh()->password));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'user@example.com']);
    }

    public function test_パスワードが8文字未満の場合は更新できないこと(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'password' => 'old-password123']);

        $response = $this->post('/reset-password', [
            ...$this->input(Password::createToken($user)),
            'password' => 'short12',
            'password_confirmation' => 'short12',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('old-password123', $user->refresh()->password));
    }

    public function test_必須項目が未入力の場合はバリデーションエラーになること(): void
    {
        $response = $this->post('/reset-password', []);

        $response->assertSessionHasErrors(['token', 'email', 'password']);
    }

    public function test_エラー後も再設定画面を表示でき入力したメールアドレスが残ること(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);

        $response = $this->from("/reset-password/{$token}")
            ->followingRedirects()
            ->post('/reset-password', [...$this->input($token), 'password_confirmation' => 'different-password']);

        $response->assertOk();
        $response->assertViewIs('auth.reset-password');
        $response->assertSee('value="user@example.com"', false);
    }

    public function test_同一接続元から5回を超えて更新を試行すると429になること(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/reset-password', $this->input('invalid-token'))->assertSessionHasErrors('email');
        }

        $this->post('/reset-password', $this->input('invalid-token'))->assertTooManyRequests();
    }

    /**
     * @return array<string, string>
     */
    private function input(string $token): array
    {
        return [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ];
    }
}
