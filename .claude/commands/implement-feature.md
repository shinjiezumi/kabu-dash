---
description: 実装計画に基づき機能を実装する（テスト込み・カバレッジ100%必須）。計画書パスか機能名を引数に渡す。例: /implement-feature docs/plans/2026-07-02-add-stock.md
allowed-tools:
  - Read
  - Grep
  - Glob
  - Bash
  - Write
  - Edit
  - Agent
---

以下の計画に基づき機能を実装します。

対象: **$ARGUMENTS**

## 手順

1. 計画書（`$ARGUMENTS` がパスの場合はそのファイル、機能名の場合は `docs/plans/` から該当ファイル）を読む。計画書がない場合は先に `/plan-feature` の実行を提案して止まる
2. `docs/architecture.md` を読み、実装テンプレート・テスト方針を把握する
3. 計画書の「実装順序」に従い、**レイヤーごとにテスト→実装→検証を刻んで進める**:
   - Domain 層（テストとセット）
   - Application 層（テストとセット）
   - Infrastructure 層 + マイグレーション（Feature テストとセット）
   - Http 層 + ルート（Feature テストとセット）
   - `AppServiceProvider` バインド
   - DDD 構造が主体の場合は `ddd-implementer` エージェントに委譲してよい
4. **各レイヤー完了ごとに検証する**（まとめて最後に、はしない）:
   ```bash
   docker compose exec app ./vendor/bin/pint
   docker compose exec app php artisan test --filter={対象テスト}
   ```
5. 全体完了後、カバレッジを確認する:
   ```bash
   docker compose exec app php artisan test --coverage --min=100
   ```
6. **100% 未満の場合**: `test-writer` エージェントで未カバー箇所のテストを追加し、100% になるまで繰り返す
7. 計画書のステータスを「実装済み」に更新する
8. `/review` でのコードレビューを案内する

## 完了条件（すべて満たすまで完了と宣言しない）

- [ ] `docker compose exec app ./vendor/bin/pint --test` がパス
- [ ] `docker compose exec app php artisan test` が全パス
- [ ] `docker compose exec app php artisan test --coverage --min=100` で Total 100.0%
- [ ] 計画書に記載した変更ファイルがすべて実装済み（差異がある場合は理由を報告）

## ルール

- 計画書にない大きな設計変更が必要になったら、独断で進めずユーザーに確認する
- テストを後回しにしない。プロダクションコードとテストは同じステップで書く
- 失敗しているテストをスキップ・削除して「パス」にしない