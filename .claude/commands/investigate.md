---
description: 既存機能を調査し、Mermaid図付きレポートを docs/investigations/ に保存する。機能名・画面名・クラス名を引数に渡す。例: /investigate 銘柄一覧画面
allowed-tools:
  - Read
  - Grep
  - Glob
  - Bash
  - Write
  - Agent
---

以下の対象について既存コードを調査します。

対象: **$ARGUMENTS**

## 手順

1. `code-explorer` エージェントに調査を依頼する（対象: $ARGUMENTS）
   - エージェントはルート→Interface→Application→Domain→Infrastructure→DB→テストの順に追跡し、Mermaid 図（sequenceDiagram / classDiagram / erDiagram）を含む定型レポートを返す
2. レポートを **`docs/investigations/{機能名スラッグ}.md`** に保存する（`docs/investigations/` がなければ作成。既存ファイルがあれば内容を最新化して上書きする）
3. レポートの要点（処理フロー概要・主要ファイル・気づいた点）を会話でも要約して伝える

**このスキルではプロダクションコードを変更しない。** 成果物は調査レポートのみ。

## ルール

- 図は必ず Mermaid 記法（```mermaid コードブロック）で含める。文章だけの説明で済ませない
- コードで確認できた事実のみを書く。推測は「未確認」と明記する
- ファイル参照は `パス:行番号` 形式で書く