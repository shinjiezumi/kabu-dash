# Claude Code ワークフローガイド

このドキュメントでは、kabu-dash の開発で Claude Code を活用するためのワークフロー・コマンド・エージェントの使い方をまとめています。

---

## 目次

1. [概要](#概要)
2. [スラッシュコマンド一覧](#スラッシュコマンド一覧)
3. [エージェント一覧](#エージェント一覧)
4. [開発ワークフロー](#開発ワークフロー)
   - [通常の機能開発](#通常の機能開発)
   - [DDD を用いた機能開発](#ddd-を用いた機能開発)
   - [PRレビュー](#prレビュー)

---

## 概要

Claude Code には以下の仕組みがあります。

| 仕組み | 場所 | 説明 |
|--------|------|------|
| **CLAUDE.md** | プロジェクトルート | プロジェクト情報・規約を Claude に提供するドキュメント。会話開始時に自動読み込みされる |
| **スラッシュコマンド** | `.claude/commands/` | `/コマンド名` で呼び出せるショートカット。繰り返し使う操作を定型化 |
| **エージェント** | `.claude/agents/` | 特定の専門知識を持つサブエージェント。コマンドや Claude から呼び出される |

---

## スラッシュコマンド一覧

### `/review [ファイルパス]`

現在の変更差分をコードレビューします。

```
/review                          # git diff の全差分をレビュー
/review src/app/Models/Stock.php # 特定ファイルをレビュー
```

**動作:**
1. 引数ありの場合 → 指定ファイルをレビュー
2. 引数なしの場合 → `git diff HEAD` の差分をレビュー
3. 差分がない場合 → `git diff origin/master...HEAD` でブランチ全体をレビュー

`reviewer` エージェントがセキュリティ・バグ・規約・パフォーマンスの観点で指摘します。

---

### `/review-pr <PR番号>`

指定した GitHub PR のコードレビューを行います。

```
/review-pr 42
```

**動作:**
1. `gh pr view` で PR の概要（タイトル・説明・ベースブランチ）を取得
2. `gh pr diff` で変更差分を取得
3. `reviewer` エージェントが PR の背景を踏まえてレビュー

---

### `/test [テスト名]`

PHPUnit テストを実行します。

```
/test                   # 全テスト実行
/test StockTest         # 特定テストクラスのみ実行
/test testCalculatePrice # 特定メソッドのみ実行
```

**動作:**
- 引数なし → `php artisan test`（全テスト）
- 引数あり → `php artisan test --filter={引数}`
- 失敗時は原因と修正案を提示

---

### `/lint [--fix]`

Laravel Pint でコーディング規約をチェックします。

```
/lint          # チェックのみ（ファイルは変更しない）
/lint --fix    # 自動修正
```

**動作:**
- `--fix` なし → `pint --test`（チェックのみ）
- `--fix` あり → `pint`（自動修正）
- 違反があればファイル名・問題箇所を説明

---

### `/ddd-model <機能・要件>`

ビジネス要件から DDD ドメインモデルを設計します。

```
/ddd-model 銘柄管理機能
/ddd-model ユーザーがポートフォリオに銘柄を追加・削除できる
```

**動作:**
1. 既存コードを読みドメインの現状を把握
2. `ddd-modeler` エージェントが以下を設計して出力:
   - ユビキタス言語（用語集）
   - 境界付けられたコンテキスト
   - 集約・エンティティ・値オブジェクト
   - ドメインイベント
   - リポジトリインターフェース
   - コンテキストマップ

---

### `/ddd-implement <モデル名・集約名>`

設計済みの DDD モデルを Laravel PHP コードとして実装します。

```
/ddd-implement Stock集約
/ddd-implement Portfolioコンテキスト
```

**動作:**
1. 既存コードを確認
2. `ddd-implementer` エージェントが以下を生成:
   - Domain 層: Entity・ValueObject・Repository インターフェース・DomainService・DomainEvent
   - Application 層: UseCase・入力 DTO
   - Infrastructure 層: Eloquent リポジトリ実装
   - `AppServiceProvider` へのバインド登録
3. マイグレーションファイルを生成
4. `tests/Unit/Domain/` にユニットテストを生成

---

## エージェント一覧

エージェントはスラッシュコマンドから自動的に呼び出されますが、Claude との会話の中で直接呼び出すこともできます。

### `reviewer`

**役割:** Laravel コードのレビュー専門エージェント

**レビュー観点:**

| 優先度 | 分類 | 内容 |
|--------|------|------|
| 🔴 Critical | セキュリティ | SQL インジェクション・XSS・CSRF・認証/認可漏れ・機密情報ハードコーディング |
| 🔴 Critical | バグ | 論理エラー・NULL 安全性・例外処理漏れ・トランザクション管理誤り |
| 🟡 Warning | パフォーマンス | N+1 問題・不要クエリ・インデックス不足 |
| 🟡 Warning | 保守性 | Fat Controller・重複コード・テスタビリティ |
| 🔵 Info | 規約 | PSR-12 準拠・命名規則・型ヒント |

**呼び出し方:**
```
/review
/review-pr 42
```

---

### `ddd-modeler`

**役割:** DDD ドメインモデルの設計専門エージェント

**設計できるもの:**
- ユビキタス言語（日本語↔英語の用語対応表）
- 境界付けられたコンテキストと責務
- 集約・集約ルート・不変条件
- エンティティとその識別子
- 値オブジェクトとバリデーション制約
- ドメインサービス
- ドメインイベント
- リポジトリインターフェース
- コンテキストマップ（コンテキスト間の関係）

**呼び出し方:**
```
/ddd-model {機能名・要件}
```

---

### `ddd-implementer`

**役割:** DDD モデルを Laravel PHP コードに変換する実装専門エージェント

**生成できるもの:**

```
src/app/
├── Domain/{BoundedContext}/
│   ├── Entity/           # エンティティ（識別子を持つオブジェクト）
│   ├── ValueObject/      # 値オブジェクト（不変・値で同一性判断）
│   ├── Aggregate/        # 集約ルート
│   ├── Repository/       # リポジトリインターフェース（抽象）
│   ├── Service/          # ドメインサービス
│   └── Event/            # ドメインイベント
├── Application/{BoundedContext}/
│   └── UseCase/          # ユースケース + 入力 DTO
└── Infrastructure/{BoundedContext}/
    └── Persistence/      # Eloquent リポジトリ実装
```

**実装方針:**
- `declare(strict_types=1)` 必須
- `final` クラスを基本とする
- `readonly` で不変性を表現
- Domain 層はフレームワーク非依存（Laravel/Eloquent を持ち込まない）
- 集約をまたぐ参照は ID のみ

**呼び出し方:**
```
/ddd-implement {モデル名・集約名}
```

---

## 開発ワークフロー

### 通常の機能開発

```
1. 実装
   └─ Claude に「〇〇機能を実装して」と依頼

2. 品質チェック
   ├─ /lint --fix   # コーディング規約を自動修正
   └─ /test         # テストを実行

3. レビュー
   └─ /review       # 変更差分をレビュー

4. PR 作成
   └─ Claude に「PR 作って」と依頼

5. PR レビュー
   └─ /review-pr {PR番号}
```

---

### DDD を用いた機能開発

```
1. ドメインモデル設計
   └─ /ddd-model {機能名・要件}
      ↓
      ユビキタス言語・集約・値オブジェクト・ドメインイベントを設計

2. 設計レビュー
   └─ Claude と対話して設計を確認・修正

3. コード実装
   └─ /ddd-implement {集約名}
      ↓
      Entity / ValueObject / UseCase / Repository / Infrastructure を生成

4. 品質チェック
   ├─ /lint --fix
   └─ /test

5. コードレビュー
   └─ /review

6. PR 作成・レビュー
   ├─ Claude に「PR 作って」と依頼
   └─ /review-pr {PR番号}
```

**具体例:**

```
# ステップ1: 銘柄管理機能のドメイン設計
/ddd-model ユーザーが銘柄をポートフォリオに追加・削除・一覧表示できる機能

# ステップ2: 設計を確認して合意

# ステップ3: Stock 集約を実装
/ddd-implement Stock集約

# ステップ4: 品質チェック
/lint --fix
/test

# ステップ5: レビュー
/review
```

---

### PRレビュー

```
# PR のコードレビューを依頼
/review-pr 42

# レビュー結果に基づき修正後、再レビュー
/review
```

---

## ファイル構成

```
.
├── CLAUDE.md                        # プロジェクト情報（Claude が自動読み込み）
├── docs/
│   └── claude-workflow.md           # このドキュメント
└── .claude/
    ├── agents/
    │   ├── reviewer.md              # コードレビューエージェント
    │   ├── ddd-modeler.md           # DDDモデリングエージェント
    │   └── ddd-implementer.md       # DDD実装エージェント
    └── commands/
        ├── review.md                # /review
        ├── review-pr.md             # /review-pr
        ├── test.md                  # /test
        ├── lint.md                  # /lint
        ├── ddd-model.md             # /ddd-model
        └── ddd-implement.md         # /ddd-implement
```
