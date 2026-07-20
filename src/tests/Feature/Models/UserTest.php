<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_nameとemailは_dbに平文で保存されないこと(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $raw = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotSame('Test User', $raw->name);
        $this->assertNotSame('test@example.com', $raw->email);
        $this->assertSame('Test User', $user->refresh()->name);
        $this->assertSame('test@example.com', $user->refresh()->email);
    }

    public function test_find_by_emailでemail_hash経由で検索できること(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $found = User::findByEmail('test@example.com');

        $this->assertNotNull($found);
        $this->assertTrue($user->is($found));
    }

    public function test_存在しないメールアドレスの場合find_by_emailはnullを返すこと(): void
    {
        $this->assertNull(User::findByEmail('unknown@example.com'));
    }

    public function test_email_hashが重複する場合は一意制約違反になること(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->expectException(QueryException::class);

        User::factory()->create([
            'name' => 'Another User',
            'email' => 'test@example.com',
        ]);
    }
}
