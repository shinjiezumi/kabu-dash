---
description: 指定したPR番号のコードレビューを行う。使い方: /review-pr 123
allowed-tools:
  - Bash
  - Read
  - Grep
  - Glob
  - Agent
---

PR番号 `$ARGUMENTS` のコードレビューを行います。

## 手順

1. `gh pr view $ARGUMENTS` で PR の概要（タイトル・説明・作成者・ベースブランチ）を取得する
2. `gh pr diff $ARGUMENTS` で変更差分を取得する
3. 変更されたファイルを必要に応じて Read ツールで確認する
4. reviewer エージェントを呼び出してレビューを実施する
5. レビュー結果を出力する

PR の背景・意図を踏まえた上で、セキュリティ・バグ・規約・パフォーマンスの観点でレビューしてください。
