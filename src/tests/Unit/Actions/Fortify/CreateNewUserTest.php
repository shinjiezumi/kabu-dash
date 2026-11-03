<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// unique ルールが DB を参照するため Laravel の TestCase を使う
final class CreateNewUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function 不正な入力(): array
    {
        return [
            '名前が空' => [['name' => ''], 'name'],
            '名前が256文字' => [['name' => str_repeat('あ', 256)], 'name'],
            'メールアドレスの形式が不正' => [['email' => 'invalid'], 'email'],
            'パスワードが不一致' => [['password_confirmation' => 'different-password'], 'password'],
            'パスワードが7文字' => [['password' => 'short12', 'password_confirmation' => 'short12'], 'password'],
        ];
    }

    public function test_正しい入力でユーザーが作成されパスワードがハッシュ化されること(): void
    {
        $user = (new CreateNewUser)->create($this->validInput());

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => '山田太郎', 'email' => 'user@example.com']);
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_パスワードは8文字で登録できること(): void
    {
        $user = (new CreateNewUser)->create([
            ...$this->validInput(),
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);

        $this->assertTrue(Hash::check('pass1234', $user->password));
    }

    public function test_名前は255文字で登録できること(): void
    {
        $name = str_repeat('あ', 255);

        $user = (new CreateNewUser)->create([...$this->validInput(), 'name' => $name]);

        $this->assertSame($name, $user->name);
    }

    public function test_メールアドレスが重複している場合は例外を投げること(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->assertValidationFailsOn('email', $this->validInput());
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('不正な入力')]
    public function test_不正な入力の場合は例外を投げユーザーを作成しないこと(array $override, string $field): void
    {
        $this->assertValidationFailsOn($field, [...$this->validInput(), ...$override]);
        $this->assertDatabaseCount('users', 0);
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

    /**
     * @param  array<string, mixed>  $input
     */
    private function assertValidationFailsOn(string $field, array $input): void
    {
        try {
            (new CreateNewUser)->create($input);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());

            return;
        }

        $this->fail('ValidationException が投げられませんでした');
    }
}
