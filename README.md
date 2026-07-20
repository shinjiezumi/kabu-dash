# kabu-dash

## 構成

```
.
├── compose.yml            # Docker Compose構成
├── .env.example           # インフラ用環境変数の雛形（NGINX_SERVER_NAME, DB_*）
├── docker/                # コンテナイメージのビルド定義
│   ├── nginx/             # Nginx
│   ├── php/               # PHP-FPM
│   └── postgresql/        # PostgreSQL
├── data/                  # ランタイムデータ（gitignore）
│   └── certs/             # ローカル用SSL証明書（mkcert）
└── src/                   # Laravelアプリケーション
```

- 起動: `docker compose up -d`
- DBデータは named volume（`pgdata`）で管理され、リポジトリ内には置かない

## 起動手順

### 1. 環境設定

```bash
cp .env.example .env            # インフラ用（docker composeが読む）
cp src/.env.example src/.env    # アプリ用（Laravelが読む）
```

### 2. ローカルHTTPS設定

mkcert でローカル用のSSL証明書を生成する。

```bash
# mkcertインストール（初回のみ）
brew install mkcert
mkcert -install

# 証明書生成（data/ はgitignore済み）
mkdir -p data/certs
mkcert -cert-file data/certs/cert.pem -key-file data/certs/key.pem \
  kabu-dash.local.shinjiezumi.com localhost 127.0.0.1
```

/etc/hosts にドメインを追記する。

```bash
echo "127.0.0.1 kabu-dash.local.shinjiezumi.com" | sudo tee -a /etc/hosts
```

#### 証明書の更新

証明書の有効期限が切れた場合（デフォルトで発行から約2年3ヶ月）は、再生成して nginx を再起動する。

```bash
# 有効期限の確認
openssl x509 -in data/certs/cert.pem -noout -dates

# 再生成（生成コマンドと同じ。既存ファイルは上書きされる）
mkcert -cert-file data/certs/cert.pem -key-file data/certs/key.pem \
  kabu-dash.local.shinjiezumi.com localhost 127.0.0.1

# 証明書はマウントされているため、nginxの再起動のみで反映される
docker compose restart nginx
```

Vite dev server（`npm run dev`）を起動中の場合は、証明書を起動時に読み込んでいるため再起動する。

ブラウザで警告が消えない場合は、ブラウザを完全に終了して再起動する（旧証明書の判定がセッション内にキャッシュされるため）。

### 3. Dockerビルド & 起動

```bash
docker compose up -d --build
```

### 4. 初期セットアップ

```bash
# Composerパッケージインストール
docker compose exec app composer install

# アプリケーションキー生成
docker compose exec app php artisan key:generate

# マイグレーション実行
docker compose exec app php artisan migrate
```

アプリケーションは https://kabu-dash.local.shinjiezumi.com で起動します。
（http アクセスは https へリダイレクトされます）

## よく使うコマンド

```bash
# コンテナ停止
docker compose down

# ログ確認
docker compose logs -f

# phpコンテナに入る
docker compose exec php sh
```
