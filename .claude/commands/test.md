---
description: PHPUnitテストを実行する。引数でフィルタ指定、--coverage でカバレッジ計測。例: /test UserTest、/test --coverage
allowed-tools:
  - Bash
---

PHPUnit テストを実行します。

## 実行コマンド

`$ARGUMENTS` に `--coverage` が含まれる場合（カバレッジ計測、100%未満で FAIL）:
```bash
docker compose exec app php artisan test --coverage --min=100
```

それ以外で引数が指定されている場合（特定テストのみ実行）:
```bash
docker compose exec app php artisan test --filter=$ARGUMENTS
```

引数なしの場合（全テスト実行）:
```bash
docker compose exec app php artisan test
```

## 結果の報告

- 失敗があれば原因と修正案を提示する
- カバレッジ計測時に 100% 未満の場合、100% に満たないファイルを列挙し、不足しているテストケースを提案する