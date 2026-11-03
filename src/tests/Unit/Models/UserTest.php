<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_キャスト定義が正しいこと(): void
    {
        $casts = (new User)->getCasts();

        $this->assertSame('datetime', $casts['email_verified_at']);
        $this->assertSame('hashed', $casts['password']);
    }

    public function test_メールアドレスの確認が必要なユーザーであること(): void
    {
        $this->assertInstanceOf(MustVerifyEmail::class, new User);
    }
}
