<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// ユーザーの保存に DB を使うため Laravel の TestCase を使う
final class ResetUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function 不正な入力(): array
    {
        return [
            'パスワードが空' => [['password' => '', 'password_confirmation' => '']],
            'パスワードが不一致' => [['password' => 'new-password123', 'password_confirmation' => 'different-password']],
            'パスワードが7文字' => [['password' => 'short12', 'password_confirmation' => 'short12']],
        ];
    }

    public function test_正しい入力でパスワードがハッシュ化されて更新されること(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);

        (new ResetUserPassword)->reset($user, ['password' => 'new-password123', 'password_confirmation' => 'new-password123']);

        $user->refresh();
        $this->assertNotSame('new-password123', $user->password);
        $this->assertTrue(Hash::check('new-password123', $user->password));
        $this->assertFalse(Hash::check('old-password123', $user->password));
    }

    public function test_パスワードは8文字で更新できること(): void
    {
        $user = User::factory()->create();

        (new ResetUserPassword)->reset($user, ['password' => 'pass1234', 'password_confirmation' => 'pass1234']);

        $this->assertTrue(Hash::check('pass1234', $user->refresh()->password));
    }

    /**
     * @param  array<string, string>  $input
     */
    #[DataProvider('不正な入力')]
    public function test_不正な入力の場合は例外を投げパスワードを変更しないこと(array $input): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);

        try {
            (new ResetUserPassword)->reset($user, $input);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('password', $e->errors());
            $this->assertTrue(Hash::check('old-password123', $user->refresh()->password));

            return;
        }

        $this->fail('ValidationException が投げられませんでした');
    }
}
