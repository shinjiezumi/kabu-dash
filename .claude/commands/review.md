---
description: 現在の変更差分をコードレビューする。引数なしで git diff HEAD のレビュー、ファイルパスを指定すると対象ファイルのレビューを行う。
allowed-tools:
  - Bash
  - Read
  - Grep
  - Glob
  - Agent
---

以下の手順でコードレビューを実施してください。

## 手順

1. レビュー対象を特定する
   - 引数が指定されている場合: `$ARGUMENTS` のファイル・ディレクトリをレビュー
   - 引数なしの場合: `git diff HEAD` でステージ済み・未ステージの変更差分をレビュー
   - 差分がない場合: `git diff origin/master...HEAD` でブランチ全体の差分をレビュー

2. reviewer エージェントを使ってレビューを実行する

レビュー対象: $ARGUMENTS
