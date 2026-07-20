# アーキテクチャ・実装規約

kabu-dash の DDD アーキテクチャと実装規約の**唯一の正となるドキュメント**。
CLAUDE.md・各エージェント・各スキルはこのファイルを参照する。規約を変更する場合はこのファイルのみを修正すること。

---

## レイヤー責務

| レイヤー | 場所 | 責務 |
|---------|------|------|
| Domain | `src/app/Domain/` | ビジネスロジックの核心。フレームワーク非依存 |
| Application | `src/app/Application/` | ユースケース。Domain 層を orchestrate する |
| Infrastructure | `src/app/Infrastructure/` | DB・外部 API 等の技術的実装 |
| Interface | `src/app/Http/` | HTTP リクエスト/レスポンスの処理 |

## ディレクトリ構成

```
src/app/
├── Domain/                          # ドメイン層（ビジネスロジックの核心）
│   └── {BoundedContext}/
│       ├── Entity/                  # エンティティ（識別子を持つオブジェクト）
│       ├── ValueObject/             # 値オブジェクト（不変・値で同一性判断）
│       ├── Aggregate/               # 集約ルート
│       ├── Repository/              # リポジトリインターフェース（抽象）
│       ├── Service/                 # ドメインサービス
│       └── Event/                   # ドメインイベント
├── Application/                     # アプリケーション層
│   └── {BoundedContext}/
│       └── UseCase/
│           ├── {Action}UseCase.php  # ユースケースクラス
│           └── {Action}Input.php    # 入力 DTO
├── Infrastructure/                  # インフラ層
│   └── {BoundedContext}/
│       └── Persistence/
│           └── Eloquent{Entity}Repository.php
├── Http/Controllers/                # インターフェース層
├── Models/                          # Eloquent モデル（Infrastructure 層からのみ使用）
└── Providers/                       # AppServiceProvider（DI バインド）
```

## DDD 実装ルール

- Domain 層に Laravel / Eloquent を持ち込まない（フレームワーク非依存を保つ）
- 集約をまたぐ参照は **ID のみ** で行う
- リポジトリは Domain 層でインターフェース定義、Infrastructure 層で実装
- `AppServiceProvider` でインターフェースと実装をバインドする
- ドメインロジックはドメイン層に置く（コントローラー・Eloquent モデルに漏れ出させない）

### PHP コード規約

- `declare(strict_types=1)` を必ず宣言する
- `final` クラスを基本とする（継承を許可する場合は理由をコメントで明示）
- `readonly` を積極的に使い不変性を表現する
- 不正な状態のオブジェクトを作れないよう、コンストラクタでバリデーションする
- スタイルは Laravel Pint（PSR-12 ベース）。マージ前に必ずパスすること
- 命名: クラス PascalCase / メソッド camelCase / DB 列 snake_case
- コメントは日本語推奨

## テスト方針

### 基本

- フレームワーク: PHPUnit（テスト DB は SQLite in-memory、`phpunit.xml` 設定済み）
- **カバレッジ 100% を必須とする**（CI で `--min=100` を強制）
- テストメソッド名は日本語で振る舞いを記述する（例: `test_価格が負の場合は例外を投げること`）

### レイヤー別のテスト要件

| 対象 | 種別 | 場所 | 必須テスト内容 |
|------|------|------|--------------|
| ValueObject | Unit | `tests/Unit/Domain/{Context}/ValueObject/` | 正常系・バリデーション例外（境界値含む）・`equals()` |
| Entity / Aggregate | Unit | `tests/Unit/Domain/{Context}/Entity/` | ビジネスロジックメソッドの全分岐・不変条件違反の例外 |
| DomainService | Unit | `tests/Unit/Domain/{Context}/Service/` | 全分岐 |
| UseCase | Unit | `tests/Unit/Application/{Context}/` | リポジトリをモックし、正常系・異常系の全分岐 |
| Eloquent リポジトリ | Feature | `tests/Feature/Infrastructure/{Context}/` | 実 DB（SQLite）での保存・取得・削除・変換の往復 |
| Controller / ルート | Feature | `tests/Feature/Http/` | HTTP ステータス・レスポンス構造・DB 状態変化・バリデーションエラー・認証認可 |

### Feature テストで必ず assert する項目

1. HTTP ステータスコード
2. レスポンス構造（view 名 / JSON 構造）
3. DB の状態変化（`assertDatabaseHas` / `assertDatabaseMissing`）
4. バリデーションエラー時の挙動（`assertSessionHasErrors` 等）

### カバレッジ確認コマンド

```bash
docker compose exec app php artisan test --coverage --min=100   # 100%未満なら FAIL
```

未達の場合、レポートの `Total: XX.X %` の上に表示されるファイル別リストで 100% 未満のファイルを特定し、
`--coverage-html` で行単位の未カバー箇所を確認できる:

```bash
docker compose exec app php artisan test --coverage-html=storage/coverage
# storage/coverage/index.html をブラウザで開く
```

## 実装テンプレート

### エンティティ

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

### 値オブジェクト

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

### リポジトリインターフェース

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

### Eloquent リポジトリ実装

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

### ユースケース

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

### ドメインイベント

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

### ServiceProvider へのバインド

```php
// app/Providers/AppServiceProvider.php
$this->app->bind(
    \App\Domain\{BoundedContext}\Repository\{Entity}RepositoryInterface::class,
    \App\Infrastructure\{BoundedContext}\Persistence\Eloquent{Entity}Repository::class,
);
```
