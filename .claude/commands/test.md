---
description: PHPUnitテストを実行する。引数でフィルタ指定可能。例: /test UserTest
allowed-tools:
  - Bash
---

PHPUnit テストを実行します。

## 実行コマンド

引数が指定されている場合（特定テストのみ実行）:
```bash
docker compose exec app php artisan test --filter=$ARGUMENTS
```

引数なしの場合（全テスト実行）:
```bash
docker compose exec app php artisan test
```

作業ディレクトリ: `/Users/shinjiezumi/work/web/php/kabu-dash`

テスト結果を表示し、失敗があれば原因と修正案を提示してください。
