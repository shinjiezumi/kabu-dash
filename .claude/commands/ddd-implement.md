---
description: DDDモデルをLaravel PHPコードとして実装する。モデル名や機能名を引数に渡す。例: /ddd-implement Stock集約
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

1. 既存コード（`src/app/`, `src/database/`）を確認し、現状を把握する
2. `ddd-implementer` エージェントを使って以下を生成する:
   - Domain層: エンティティ・値オブジェクト・集約・リポジトリインターフェース・ドメインサービス・ドメインイベント
   - Application層: ユースケース・入力DTO
   - Infrastructure層: Eloquentリポジトリ実装
   - `AppServiceProvider` へのバインド登録
3. 必要なマイグレーションファイルを生成する
4. 生成したクラスのユニットテストを `src/tests/Unit/Domain/` に作成する

## ディレクトリ規則

```
src/app/
├── Domain/{BoundedContext}/
│   ├── Entity/
│   ├── ValueObject/
│   ├── Aggregate/
│   ├── Repository/
│   ├── Service/
│   └── Event/
├── Application/{BoundedContext}/UseCase/
└── Infrastructure/{BoundedContext}/Persistence/
```

実装後、`/review` でコードレビューを実施することを推奨します。
