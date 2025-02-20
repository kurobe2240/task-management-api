# タスク管理API

## 概要
このプロジェクトは、モダンなタスク管理システムのAPIとフロントエンドを提供します。

## 技術スタック

### バックエンド
- PHP 8.2
- .NET 8
- MySQL 8.0
- Composer
- PHPUnit

### フロントエンド
- React 18
- TypeScript
- Vite
- TailwindCSS

## 機能
- ユーザー認証（JWT）
- プロジェクト管理
- タスク管理
- 検索機能
- アクティビティログ

## 開発環境のセットアップ

### 必要条件
- PHP 8.2以上
- .NET SDK 8.0以上
- Node.js 18以上
- MySQL 8.0以上
- Composer
- npm または yarn

### インストール手順

1. リポジトリのクローン
```bash
git clone https://github.com/yourusername/task-management.git
cd task-management
```

2. PHPの依存関係のインストール
```bash
composer install
```

3. フロントエンドの依存関係のインストール
```bash
cd frontend
npm install
```

4. 環境変数の設定
```bash
cp .env.example .env
# .envファイルを編集して必要な設定を行う
```

5. データベースのセットアップ
```bash
php setup_database.php
```

6. 開発サーバーの起動
```bash
# PHPサーバー
php -S localhost:8000 -t public

# フロントエンド開発サーバー
cd frontend
npm run dev
```

## テスト
```bash
# PHPユニットテスト
./vendor/bin/phpunit

# フロントエンドテスト
cd frontend
npm test
```

## デプロイ
デプロイの詳細な手順については、[デプロイメントガイド](docs/deployment.md)を参照してください。

## API ドキュメント
APIの詳細なドキュメントは、以下のURLで確認できます：
- 開発環境: http://localhost:5015/swagger
- 本番環境: https://your-api-domain.com/swagger

## ライセンス
このプロジェクトはMITライセンスの下で公開されています。

## 貢献
バグ報告や機能要望は、GitHubのIssueで受け付けています。
プルリクエストも歓迎します。

## 作者
[Your Name]

## 謝辞
このプロジェクトは以下のオープンソースソフトウェアを使用しています：
- React
- .NET
- PHP
- その他の依存ライブラリ
