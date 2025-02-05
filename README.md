# Task Management API

タスク管理のためのシンプルなRESTful APIです。

## 機能

- タスクの作成
- タスク一覧の取得
- タスクの詳細取得
- タスクの更新
- タスクの削除

## 技術スタック

- .NET 8.0
- Entity Framework Core
- SQL Server
- Swagger UI
- AutoMapper
- FluentValidation

## APIエンドポイント

| メソッド | エンドポイント | 説明 |
|----------|----------------|------|
| GET | /api/TaskItems | タスク一覧を取得 |
| GET | /api/TaskItems/{id} | 指定したIDのタスクを取得 |
| POST | /api/TaskItems | 新しいタスクを作成 |
| PUT | /api/TaskItems/{id} | 指定したIDのタスクを更新 |
| DELETE | /api/TaskItems/{id} | 指定したIDのタスクを削除 |

## 開発環境のセットアップ

1. リポジトリをクローン
```bash
git clone [repository-url]
```

2. プロジェクトディレクトリに移動
```bash
cd TaskManagement.API
```

3. 依存関係をインストール
```bash
dotnet restore
```

4. アプリケーションを実行
```bash
dotnet run
```

5. ブラウザで以下のURLにアクセス
```
http://localhost:5015/swagger
```

## ライセンス

MIT License 