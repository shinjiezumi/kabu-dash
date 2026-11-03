# 実装テンプレート

kabu-dash の DDD 実装テンプレート。規約とテスト方針は [architecture.md](./architecture.md) を参照。

## エンティティ

```php
<?php

declare(strict_types=1);

namespace App\Domain\{BoundedContext}\Entity;

final class {EntityName}
{
    public function __construct(
        private readonly {IdType} $id,
        private {FieldType} ${field},
    ) {}

    public function getId(): {IdType}
    {
        return $this->id;
    }

    // ビジネスロジックメソッド
    public function {action}(): void
    {
        // ドメインルールを実装
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
```

## 値オブジェクト

```php
<?php

declare(strict_types=1);

namespace App\Domain\{BoundedContext}\ValueObject;

final class {ValueObjectName}
{
    public function __construct(
        private readonly {type} $value,
    ) {
        $this->validate($value);
    }

    private function validate({type} $value): void
    {
        // バリデーション（不正な場合は例外を投げる）
        if (/* 不正な条件 */) {
            throw new \InvalidArgumentException("{バリデーションエラーメッセージ}");
        }
    }

    public function getValue(): {type}
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

## リポジトリインターフェース

```php
<?php

declare(strict_types=1);

namespace App\Domain\{BoundedContext}\Repository;

interface {Entity}RepositoryInterface
{
    public function findById({IdType} $id): ?{Entity};
    public function save({Entity} $entity): void;
    public function delete({IdType} $id): void;
}
```

## Eloquent リポジトリ実装

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\{BoundedContext}\Persistence;

use App\Domain\{BoundedContext}\Entity\{Entity};
use App\Domain\{BoundedContext}\Repository\{Entity}RepositoryInterface;
use App\Models\{EloquentModel};

final class Eloquent{Entity}Repository implements {Entity}RepositoryInterface
{
    public function findById({IdType} $id): ?{Entity}
    {
        $model = {EloquentModel}::find($id->getValue());
        return $model ? $this->toEntity($model) : null;
    }

    public function save({Entity} $entity): void
    {
        {EloquentModel}::updateOrCreate(
            ['id' => $entity->getId()->getValue()],
            $this->toArray($entity),
        );
    }

    private function toEntity({EloquentModel} $model): {Entity}
    {
        // Eloquent モデル → ドメインエンティティ変換
    }

    private function toArray({Entity} $entity): array
    {
        // ドメインエンティティ → 配列変換
    }
}
```

## ユースケース

```php
<?php

declare(strict_types=1);

namespace App\Application\{BoundedContext}\UseCase;

final class {Action}UseCase
{
    public function __construct(
        private readonly {Entity}RepositoryInterface $repository,
    ) {}

    public function execute({Action}Input $input): {OutputType}
    {
        // 1. リポジトリからエンティティ取得
        // 2. ドメインロジック実行
        // 3. リポジトリで永続化
        // 4. 結果を返す
    }
}
```

## ドメインイベント

```php
<?php

declare(strict_types=1);

namespace App\Domain\{BoundedContext}\Event;

final class {EventName}
{
    public function __construct(
        public readonly {IdType} $aggregateId,
        public readonly \DateTimeImmutable $occurredAt,
        // イベント固有のデータ
    ) {}
}
```

## ServiceProvider へのバインド

```php
// app/Providers/AppServiceProvider.php
$this->app->bind(
    \App\Domain\{BoundedContext}\Repository\{Entity}RepositoryInterface::class,
    \App\Infrastructure\{BoundedContext}\Persistence\Eloquent{Entity}Repository::class,
);
```
