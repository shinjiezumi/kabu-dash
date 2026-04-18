---
description: ビジネス要件からDDDドメインモデルを設計する。要件・機能名・ユーザーストーリーを引数に渡す。例: /ddd-model ポートフォリオ管理機能
allowed-tools:
  - Read
  - Grep
  - Glob
  - Bash
  - Agent
---

以下の要件・機能についてDDDドメインモデルを設計します。

対象: **$ARGUMENTS**

## 手順

1. 既存コード（`src/app/`, `src/database/migrations/`, `src/routes/`）を読み、現在のドメイン状況を把握する
2. `ddd-modeler` エージェントを使って以下を設計する:
   - ユビキタス言語
   - 境界付けられたコンテキスト
   - 集約・エンティティ・値オブジェクト
   - ドメインイベント
   - リポジトリインターフェース
   - コンテキストマップ
3. 設計結果を出力する
4. 実装に進む場合は `/ddd-implement` コマンドを使うよう案内する
