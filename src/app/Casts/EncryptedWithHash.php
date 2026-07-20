<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * 値を暗号化して保存しつつ、検索・一意性チェック用に決定的なハッシュ値を
 * `{属性名}_hash` 列へ保存するキャスト。
 *
 * email のように暗号化後も検索が必要な PII 列で使用する（例: name のように検索不要なら
 * 標準の 'encrypted' キャストで十分）。ハッシュは APP_KEY をキーとした HMAC のため、
 * APP_KEY を知らない第三者はハッシュから元の値を推測できない。
 *
 * @implements CastsAttributes<string, string>
 */
final class EncryptedWithHash implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [
                $key => null,
                "{$key}_hash" => null,
            ];
        }

        return [
            $key => Crypt::encryptString($value),
            "{$key}_hash" => self::hash($value),
        ];
    }

    /**
     * 検索・一意性チェック用のハッシュ値を計算する。
     * 大文字小文字の揺れを無視して検索できるよう小文字化してからハッシュ化する。
     */
    public static function hash(string $value): string
    {
        return hash_hmac('sha256', mb_strtolower($value), (string) config('app.key'));
    }
}
