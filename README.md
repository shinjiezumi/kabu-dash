# kabu-dash

## 起動手順

### 1. 依存パッケージのインストール

```bash
composer install
npm install
```

### 2. 環境設定

```bash
cp .env.example .env
php artisan key:generate
```

### 3. データベースのマイグレーション

```bash
php artisan migrate
```

### 4. アプリケーションの起動

```bash
# 開発サーバー起動
php artisan serve

# フロントエンドのビルド（開発用ウォッチモード）
npm run dev
```

アプリケーションは http://localhost:8000 で起動します。
