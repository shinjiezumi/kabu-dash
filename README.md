# kabu-dash

## 構成

```
.
├── compose.yml
├── docker/
│   ├── nginx/        # Nginx
│   ├── php/          # PHP-FPM
│   └── postgresql/   # PostgreSQL
└── src/              # Laravelアプリケーション
```

## 起動手順

### 1. 環境設定

```bash
cp src/.env.example src/.env
```

### 2. Dockerビルド & 起動

```bash
docker compose up -d --build
```

### 3. 初期セットアップ

```bash
# Composerパッケージインストール
docker compose exec php composer install

# アプリケーションキー生成
docker compose exec php php artisan key:generate

# マイグレーション実行
docker compose exec php php artisan migrate
```

アプリケーションは http://localhost で起動します。

## よく使うコマンド

```bash
# コンテナ停止
docker compose down

# ログ確認
docker compose logs -f

# phpコンテナに入る
docker compose exec php sh
```
