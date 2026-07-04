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

## アーキテクチャ・実装規約

このプロジェクトは DDD（ドメイン駆動設計）を採用している。
**ディレクトリ構成・レイヤー責務・DDD ルール・コード規約・テスト方針は @docs/architecture.md が唯一の正。**
実装・テスト・レビューの前に必ず参照すること。

要点（詳細は architecture.md）:

- Domain 層に Laravel / Eloquent を持ち込まない
- 集約をまたぐ参照は ID のみ
- リポジトリは Domain 層でインターフェース定義、Infrastructure 層で実装し `AppServiceProvider` でバインド
- `declare(strict_types=1)` / `final` / `readonly` を基本とする
- **テストカバレッジ 100% 必須**（CI で強制）

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
docker compose exec app php artisan test --coverage --min=100    # カバレッジ確認（100%未満でFAIL）
docker compose exec app ./vendor/bin/pint                        # コーディング規約修正
docker compose exec app ./vendor/bin/pint --test                 # 規約チェックのみ（修正なし）
```

## 実装タスクの完了条件

コード変更を伴うタスクは、以下がすべてパスするまで完了と宣言しないこと。

1. `docker compose exec app ./vendor/bin/pint --test`
2. `docker compose exec app php artisan test`
3. `docker compose exec app php artisan test --coverage --min=100`（機能追加・リファクタリング時）

## コーディング規約（要約）

- **スタイル**: Laravel Pint (PSR-12 ベース) — PRマージ前に必ずパスすること
- **コミットメッセージ**: 日本語で記述（例: `ユーザー認証機能追加`）
- **コメント**: 日本語推奨
- **命名**: クラス・メソッドは英語 (Laravel 規約に従う)

## CI (GitHub Actions)

PR 作成・プッシュ時に自動実行:

| ジョブ | ツール | 内容 |
|--------|--------|------|
| lint | Laravel Pint | コーディング規約チェック |
| security | composer audit / npm audit | 依存パッケージの脆弱性スキャン |
| test | PHPUnit | 自動テスト + カバレッジ100%チェック |

## ワークフロー・スキル・エージェント

開発ワークフロー（計画→実装→調査→リファクタリング→レビュー）と、利用可能なスラッシュコマンド・エージェントの一覧は @docs/claude-workflow.md を参照。

## 環境変数

`.env` は `src/` 直下に配置。`src/.env.example` を参考に作成。
本番環境では `APP_DEBUG=false`、`APP_ENV=production` に設定すること。
