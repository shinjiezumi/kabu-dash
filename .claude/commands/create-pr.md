---
description: 現在のブランチから GitHub PR を作成する。未コミットの変更があれば品質ゲート確認後にコミットし、push して PR を作成する。引数で PR タイトルを指定可能。例: /create-pr ローカル環境のHTTPS化
allowed-tools:
  - Bash
  - Read
  - Grep
  - Glob
---

以下の手順で GitHub PR を作成してください。

指定タイトル（任意）: $ARGUMENTS

## 手順

1. 現状を並行して確認する:
   ```bash
   git status
   git diff HEAD
   git branch --show-current
   git log origin/master..HEAD --oneline
   ```

2. ブランチを確認する:
   - `master` にいる場合: 変更内容に応じた `feature/{英語スラッグ}` ブランチを作成して移動する
   - 差分（未コミット変更・未 push コミット）が何もない場合: PR を作る対象がない旨を報告して止まる

3. 未コミットの変更がある場合はコミットする:
   - **コード変更（`src/` 配下の PHP・JS 等）を含む場合は、コミット前に品質ゲートを実行する**:
     ```bash
     docker compose exec app ./vendor/bin/pint --test
     docker compose exec app php artisan test
     docker compose exec app php artisan test --coverage --min=100
     ```
     失敗した場合はコミット・PR 作成に進まず、失敗内容を報告して止まる
   - ドキュメント・設定ファイルのみの変更なら品質ゲートは省略してよい
   - コミットメッセージは**日本語**で、変更の目的が分かる一文にする（例: `ユーザー認証機能追加`）

4. push する:
   ```bash
   git push -u origin {ブランチ名}
   ```

5. PR を作成する（base は `master`）:
   ```bash
   gh pr create --base master --title "{タイトル}" --body "{本文}"
   ```
   - タイトル: `$ARGUMENTS` が指定されていればそれを使う。なければブランチ内の全コミットから日本語で要約する
   - 本文は以下のテンプレートに従う:

     ```markdown
     ## 概要

     {変更の目的・背景を 1〜3 文で}

     ## 変更内容

     - {主な変更点を箇条書き}

     ## テスト

     - [ ] `./vendor/bin/pint --test` パス
     - [ ] `php artisan test` 全パス
     - [ ] カバレッジ 100%（コード変更がある場合）

     🤖 Generated with [Claude Code](https://claude.com/claude-code)
     ```

6. 作成した PR の URL を報告し、`/review-pr {PR番号}` でのレビューを案内する

## ルール

- PR のタイトル・本文・コミットメッセージは日本語で書く
- 品質ゲートが失敗している状態で PR を作成しない（ドキュメントのみの変更は除く）
- `git push --force` は使わない
- 既に同ブランチの PR が存在する場合（`gh pr view` で確認）は、新規作成せず push のみ行い既存 PR の URL を報告する
