# kabu-dash

株式ダッシュボードアプリケーション。Laravel 13 + PostgreSQL + Docker で構成。

## アーキテクチャ・実装規約

このプロジェクトは DDD（ドメイン駆動設計）を採用している。
**ディレクトリ構成・レイヤー責務・DDD ルール・コード規約・テスト方針は @docs/architecture.md が唯一の正。**
実装・テスト・レビューの前に必ず参照すること。

## コマンド実行

`php` / `artisan` / `composer` / `pint` は `docker compose exec app` 経由で実行する。

## 実装タスクの完了条件

コード変更を伴うタスクは、以下がすべてパスするまで完了と宣言しないこと。

1. `docker compose exec app ./vendor/bin/pint --test`
2. `docker compose exec app php artisan test`
3. `docker compose exec app php artisan test --coverage --min=100`（機能追加・リファクタリング時）

## コーディング規約（要約）

- **コミットメッセージ**: 日本語で記述（例: `ユーザー認証機能追加`）
- **コメント**: 日本語推奨

## ワークフロー・スキル・エージェント

開発ワークフロー（計画→実装→調査→リファクタリング→レビュー）と、利用可能なスラッシュコマンド・エージェントの一覧は `docs/claude-workflow.md` を参照。

## 環境変数

`.env` は `src/` 直下に配置。`src/.env.example` を参考に作成。