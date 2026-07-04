---
description: テストを安全網としたリファクタリングを行う（振る舞いは変えない）。対象のファイル・ディレクトリ・機能名を引数に渡す。例: /refactor src/app/Http/Controllers/StockController.php
allowed-tools:
  - Read
  - Grep
  - Glob
  - Bash
  - Write
  - Edit
  - Agent
---

以下の対象をリファクタリングします。**外部から観測可能な振る舞いは一切変えません。**

対象: **$ARGUMENTS**

## 手順

### 1. 安全網の確認（実施前に必ず）

1. `docs/architecture.md` を読み、あるべき構造を把握する
2. 対象範囲の既存テストを特定し、実行する:
   ```bash
   docker compose exec app php artisan test
   ```
   - **テストが失敗している場合**: リファクタリングを中断し、失敗内容を報告してユーザーの指示を仰ぐ
3. 対象コードのカバレッジを確認する:
   ```bash
   docker compose exec app php artisan test --coverage --min=100
   ```
   - **対象のカバレッジが不足している場合**: 先に `test-writer` エージェントで現在の振る舞いを固定するテスト（characterization test）を追加し、グリーンを確認してからリファクタリングに進む

### 2. リファクタリング実施

1. 対象の問題点（重複・Fat Controller・ドメインロジックの漏出・命名など）を列挙し、変更方針を短く示す
2. 小さいステップで変更する。1ステップごとにテストを実行し、グリーンを維持する:
   ```bash
   docker compose exec app php artisan test
   ```
3. テストコード自体の変更は「リファクタリングで必要になった参照先の変更」のみに留める。**assert の内容（期待値）は変えない**。変えたくなった場合は振る舞いが変わっている証拠なので中断して報告する

### 3. 完了確認

- [ ] `docker compose exec app ./vendor/bin/pint --test` がパス
- [ ] `docker compose exec app php artisan test` が全パス
- [ ] `docker compose exec app php artisan test --coverage --min=100` で Total 100.0%

### 4. 報告

変更内容を before / after の要点で報告する:
- 何をどう変えたか（ファイル単位）
- 振る舞いが変わっていないことをどう担保したか（実行したテスト）
- 積み残した改善候補（あれば）

必要に応じて `/review` でのレビューを案内する。