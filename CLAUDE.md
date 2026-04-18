# kabu-dash

株式ダッシュボードアプリケーション。Laravel 13 + PostgreSQL + Docker で構成。

## 技術スタック

| レイヤー | 技術 |
|----------|------|
| バックエンド | Laravel 13 / PHP 8.3 |
| フロントエンド | Vite / TailwindCSS v4 |
| DB | PostgreSQL 16 |
| Web サーバー | Nginx |
| コンテナ | Docker Compose |

## ディレクトリ構成

```
.
├── compose.yml
├── docker/
│   ├── nginx/               # Nginx設定
│   ├── php/                 # PHP-FPM Dockerfile (appサービス)
│   └── postgresql/          # PostgreSQL Dockerfile + data/pgdata/
└── src/                     # Laravelアプリケーション
    ├── app/
    │   ├── Http/Controllers/
    │   └── Models/
    ├── config/
    ├── database/
    │   ├── factories/
    │   ├── migrations/
    │   └── seeders/
    ├── resources/
    │   ├── css/
    │   ├── js/
    │   └── views/
    ├── routes/
    └── tests/
        ├── Feature/
        └── Unit/
```

## よく使うコマンド

### Docker 操作

```bash
docker compose up -d --build        # 起動（イメージ再ビルド）
docker compose up -d                # 起動
docker compose down                 # 停止
docker compose exec app bash        # appコンテナに入る
docker compose logs -f              # ログ確認
```

### Laravel (appコンテナ内で実行)

```bash
docker compose exec app php artisan migrate                      # マイグレーション
docker compose exec app php artisan migrate:rollback             # ロールバック
docker compose exec app php artisan make:model Foo -mcr          # モデル+マイグレーション+コントローラー
docker compose exec app php artisan make:controller FooController
docker compose exec app php artisan make:migration create_foo_table
docker compose exec app php artisan route:list                   # ルート一覧
docker compose exec app php artisan tinker                       # REPL
```

### テスト・品質チェック

```bash
docker compose exec app php artisan test                         # テスト実行
docker compose exec app php artisan test --filter=FooTest        # 特定テスト
docker compose exec app ./vendor/bin/pint                        # コーディング規約修正
docker compose exec app ./vendor/bin/pint --test                 # 規約チェックのみ（修正なし）
```

## コーディング規約

- **スタイル**: Laravel Pint (PSR-12 ベース) — PRマージ前に必ずパスすること
- **コミットメッセージ**: 日本語で記述（例: `ユーザー認証機能追加`）
- **コメント**: 日本語推奨
- **命名**: クラス・メソッドは英語 (Laravel 規約に従う)

## テスト方針

- フレームワーク: PHPUnit
- DB: テスト時は SQLite in-memory（`phpunit.xml` で設定済み）
- Feature テスト: HTTP リクエスト〜レスポンスを通したテスト
- Unit テスト: 単一クラス・メソッドのテスト

## CI (GitHub Actions)

PR 作成・プッシュ時に自動実行:

| ジョブ | ツール | 内容 |
|--------|--------|------|
| lint | Laravel Pint | コーディング規約チェック |
| security | composer audit / npm audit | 依存パッケージの脆弱性スキャン |
| test | PHPUnit | 自動テスト |

## 環境変数

`.env` は `src/` 直下に配置。`src/.env.example` を参考に作成。
本番環境では `APP_DEBUG=false`、`APP_ENV=production` に設定すること。
