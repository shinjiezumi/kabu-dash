---
description: Laravel Pint でコーディング規約をチェック・修正する。--fix を付けると自動修正、それ以外はチェックのみ。例: /lint --fix
allowed-tools:
  - Bash
---

Laravel Pint でコーディング規約をチェックします。

## 実行コマンド

`$ARGUMENTS` に `--fix` が含まれる場合（自動修正モード）:
```bash
docker compose exec app ./vendor/bin/pint
```

それ以外（チェックのみ、修正なし）:
```bash
docker compose exec app ./vendor/bin/pint --test
```

作業ディレクトリ: `/Users/shinjiezumi/work/web/php/kabu-dash`

結果を表示し、違反があればどのファイルの何が問題かを説明してください。
