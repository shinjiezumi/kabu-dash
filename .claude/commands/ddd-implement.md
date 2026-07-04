---
description: DDDモデルをLaravel PHPコードとして実装する（テスト込み・カバレッジ100%必須）。モデル名や機能名を引数に渡す。例: /ddd-implement Stock集約
allowed-tools:
  - Read
  - Grep
  - Glob
  - Bash
  - Write
  - Edit
  - Agent
---

以下のDDDモデルをLaravel PHPコードとして実装します。

対象: **$ARGUMENTS**

## 手順

1. `docs/architecture.md` を読む（ディレクトリ構成・実装テンプレート・テスト方針はこれが正）
2. 既存コード（`src/app/`, `src/database/`）を確認し、現状を把握する
3. `ddd-implementer` エージェントに実装を依頼する。エージェントは Domain / Application / Infrastructure 各層のコード・マイグレーション・テスト・`AppServiceProvider` バインドまで生成する
4. 完了条件を確認する:
   - `docker compose exec app ./vendor/bin/pint --test` がパス
   - `docker compose exec app php artisan test` が全パス
   - `docker compose exec app php artisan test --coverage --min=100` で Total 100.0%
5. カバレッジ不足があれば `test-writer` エージェントで補完する

実装後、`/review` でコードレビューを実施することを推奨します。