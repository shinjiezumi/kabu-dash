<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_登録画面が表示されること(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    public function test_ログイン画面に登録画面へのリンクがあること(): void
    {
        $this->get('/login')->assertSee(route('register'));
    }

    public function test_認証済みユーザーは登録画面からダッシュボードへリダイレクトされること(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/register');

        $response->assertRedirect('/dashboard');
    }

    public function test_正しい入力で登録するとログイン状態でダッシュボードへ遷移すること(): void
    {
        $response = $this->post('/register', $this->validInput());

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['name' => '山田太郎', 'email' => 'user@example.com']);
        $this->assertTrue(Hash::check('password123', User::sole()->password));
    }

    public function test_大文字混じりのメールアドレスは小文字で保存されログインできること(): void
    {
        $this->post('/register', [...$this->validInput(), 'email' => 'USER@Example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user@example.com']);

        $this->post('/logout');
        $this->assertGuest();

        $response = $this->post('/login', ['email' => 'USER@Example.com', 'password' => 'password123']);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_登録済みのメールアドレスでは登録できないこと(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->post('/register', $this->validInput());

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_大文字小文字だけが異なるメールアドレスでは登録できないこと(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->post('/register', [...$this->validInput(), 'email' => 'USER@Example.com']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_パスワードが一致しない場合は登録できないこと(): void
    {
        $response = $this->post('/register', [...$this->validInput(), 'password_confirmation' => 'different-password']);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_パスワードが8文字未満の場合は登録できないこと(): void
    {
        $response = $this->post('/register', [
            ...$this->validInput(),
            'password' => 'short12',
            'password_confirmation' => 'short12',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_同一接続元から5回を超えて登録を試行すると429になること(): void
    {
        $input = [...$this->validInput(), 'password_confirmation' => 'different-password'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', $input)->assertSessionHasErrors('password');
        }

        $this->post('/register', $this->validInput())->assertTooManyRequests();

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_登録のレート制限は別の接続元に影響しないこと(): void
    {
        $input = [...$this->validInput(), 'password_confirmation' => 'different-password'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', $input);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])
            ->post('/register', $input)
            ->assertSessionHasErrors('password');
    }

    public function test_登録画面の表示はレート制限の対象外であること(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->get('/register')->assertOk();
        }
    }

    public function test_配列の名前を送った後も登録画面を表示できること(): void
    {
        $response = $this->from('/register')
            ->followingRedirects()
            ->post('/register', [...$this->validInput(), 'name' => ['山田太郎']]);

        $response->assertOk();
        $response->assertViewIs('auth.register');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_入力エラー後は名前とメールアドレスの入力値が画面に残ること(): void
    {
        $response = $this->from('/register')
            ->followingRedirects()
            ->post('/register', [...$this->validInput(), 'password_confirmation' => 'different-password']);

        $response->assertOk();
        $response->assertSee('value="山田太郎"', false);
        $response->assertSee('value="user@example.com"', false);
    }

    /**
     * @return array<string, string>
     */
    private function validInput(): array
    {
        return [
            'name' => '山田太郎',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }
}
