<?php

declare(strict_types=1);

namespace Tests\Unit\Casts;

use App\Casts\EncryptedWithHash;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EncryptedWithHashTest extends TestCase
{
    public function test_値がnullの場合getはnullを返すこと(): void
    {
        $cast = new EncryptedWithHash;

        $this->assertNull($cast->get(new User, 'email', null, []));
    }

    public function test_復号できない値の場合getはnullを返すこと(): void
    {
        $cast = new EncryptedWithHash;

        $this->assertNull($cast->get(new User, 'email', 'not-encrypted-value', []));
    }

    public function test_暗号化済みの値をgetで復号できること(): void
    {
        $cast = new EncryptedWithHash;
        $encrypted = Crypt::encryptString('test@example.com');

        $this->assertSame('test@example.com', $cast->get(new User, 'email', $encrypted, []));
    }

    public function test_値がnullの場合setは属性とハッシュ列をnullにすること(): void
    {
        $cast = new EncryptedWithHash;

        $result = $cast->set(new User, 'email', null, []);

        $this->assertSame([
            'email' => null,
            'email_hash' => null,
        ], $result);
    }

    public function test_値を暗号化しハッシュ列と共に設定すること(): void
    {
        $cast = new EncryptedWithHash;

        $result = $cast->set(new User, 'email', 'test@example.com', []);

        $this->assertNotSame('test@example.com', $result['email']);
        $this->assertSame('test@example.com', Crypt::decryptString($result['email']));
        $this->assertSame(EncryptedWithHash::hash('test@example.com'), $result['email_hash']);
    }

    public function test_hashは大文字小文字を無視して同じ値を返すこと(): void
    {
        $this->assertSame(
            EncryptedWithHash::hash('Test@Example.com'),
            EncryptedWithHash::hash('test@example.com'),
        );
    }

    public function test_hashは異なる値に対して異なる値を返すこと(): void
    {
        $this->assertNotSame(
            EncryptedWithHash::hash('a@example.com'),
            EncryptedWithHash::hash('b@example.com'),
        );
    }
}
