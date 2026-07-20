<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // email は暗号化されているため where('email', ...) では既存行を検索できない。
        // findByEmail() (email_hash 経由の検索) を使い、再実行してもエラーにならないようにする。
        if (User::findByEmail('test@example.com') === null) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
