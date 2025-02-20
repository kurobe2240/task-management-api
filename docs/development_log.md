# ポートフォリオ作品開発ログ

## プロジェクト情報

### リポジトリ情報
- GitHub: https://github.com/kurobe2240
- 本番環境（Vercel）: https://vercel.com/kurobe2240s-projects/portfolio-production
- サイトURL: https://portfolio-production-psi.vercel.app/

### 環境情報
- OS: Windows 10 (10.0.26100)
- 開発ツール: 
  - XAMPP（ポータブル版）- 場所: C:\Users\yamam\Documents\tools
  - Visual Studio Code
  - SQL Server Express

## 開発履歴

### 2024-02-04
- ポートフォリオサイトの基本構築完了
- 開発ログの作成開始
- REST API開発プロジェクトの開始
  - プロジェクト名：TaskManagement.API
  - 技術スタック選定完了
    - .NET 8 Web API
    - Entity Framework Core
    - SQL Server Express
    - Swagger UI
    - AutoMapper
    - FluentValidation

### 2024-02-05
#### タスク管理APIの基本実装
1. プロジェクト構造の設定
   - TaskManagement.APIプロジェクトの作成
   - 必要なNuGetパッケージの追加
   - フォルダ構造の整理（Controllers, Models, Data, DTOs, Validators）

2. データベース設計と実装
   - Entity Framework Coreの設定
   - TaskItemモデルの実装
   - ApplicationDbContextの設定
   - マイグレーションの実行

3. APIエンドポイントの実装
   - TaskItemsControllerの作成
   - CRUD操作の実装
   - DTOとAutoMapperの設定
   - FluentValidationによるバリデーション

4. デプロイ試行
   - Render.comでのデプロイ試行（失敗）
     - PostgreSQL対応の実装
     - Docker対応の実装
     - 認証エラーにより断念
   - Azure App Serviceへの移行決定
     - PostgreSQL関連コードの削除
     - SQL Server設定の最適化
     - Docker関連ファイルの削除

#### 実装済み機能
- [x] タスクのCRUD操作の基本実装
- [x] Entity Framework Coreによるデータアクセス
- [x] DTOパターンの実装
- [x] FluentValidationによる入力検証
- [x] Swagger UIによるAPI文書化
- [x] ヘルスチェックエンドポイント
- [x] CORS設定
- [x] デモデータの自動シード

#### 未解決の問題
1. デプロイ環境
   - [ ] Azure App Serviceへのデプロイ
   - [ ] Azure SQL Databaseの設定
   - [ ] 本番環境用の接続文字列の設定

2. セキュリティ
   - [ ] JWT認証の実装
   - [ ] ユーザー認証の実装
   - [ ] APIキーの実装

3. 機能拡張
   - [ ] プロジェクト管理機能
   - [ ] ユーザー管理機能
   - [ ] タスクの優先度設定
   - [ ] タスクの期限アラート

4. テストとドキュメント
   - [ ] ユニットテストの作成
   - [ ] 統合テストの作成
   - [ ] API仕様書の作成
   - [ ] デプロイ手順書の作成

#### 次のステップ
1. Azure App Serviceへのデプロイ
   - Azure Portalでのリソース作成
   - SQL Databaseの設定
   - CI/CDパイプラインの構築

2. セキュリティ機能の実装
   - JWT認証の追加
   - ユーザー管理機能の実装
   - APIキーの設定

3. 機能拡張
   - プロジェクト管理機能の追加
   - タスクの優先度と期限管理の実装
   - 検索・フィルタリング機能の追加

#### 技術的な課題
1. デプロイ関連
   - Azure App Serviceの設定最適化
   - 環境変数の管理
   - ログ収集の設定

2. パフォーマンス
   - データベースインデックスの最適化
   - キャッシュ戦略の検討
   - N+1問題への対応

3. セキュリティ
   - HTTPS強制の設定
   - CORS設定の最適化
   - SQL Injectionの防止

#### 参考資料・証跡
1. ソースコード
   - GitHub: https://github.com/kurobe2240/task-management-api
   - コミット履歴で実装の進捗を確認可能

2. 使用技術のバージョン
   - .NET 8.0
   - Entity Framework Core 8.0.1
   - FluentValidation.AspNetCore 11.3.0
   - AutoMapper 12.0.1
   - Swashbuckle.AspNetCore 6.5.0

3. 設定ファイル
   - appsettings.json: データベース接続設定
   - Program.cs: アプリケーション構成
   - TaskManagement.API.csproj: プロジェクト依存関係

## メモ
- フリーランス向けポートフォリオサイトに乗せる作品を制作中
- バックエンド開発に特化した実装を重視
- セキュリティとスケーラビリティを考慮した設計を目指す
- 実務で使える技術スタックの選定を完了

### 2024-02-06
#### ElasticSearchの導入と設定
1. ElasticSearch関連の実装
   - ElasticSearchSettingsの追加
   - SearchServiceの実装
   - Program.csへのElasticSearch設定の追加
   - ApplicationDbContextへのUsersテーブルの追加

2. レート制限の実装
   - RateLimitingMiddlewareExtensionsの実装
   - レート制限の設定最適化
   - 固定のRetryAfter値の設定

3. 検索機能の実装
   - タスク検索機能の実装
   - プロジェクト検索機能の実装
   - ソート機能の実装
   - ファセット検索の実装

#### 実装済み機能
- [x] ElasticSearchによる検索機能
- [x] レート制限機能
- [x] ユーザーテーブルの追加
- [x] 検索結果のソート機能
- [x] ファセット検索機能

#### 技術的な改善
1. SearchServiceの改善
   - ソートロジックの最適化
   - null参照の安全性向上
   - エラーハンドリングの強化

2. レート制限の改善
   - 固定のRetryAfter値の設定
   - レスポンスメッセージの日本語化
   - キュー制限の設定

#### 次のステップ
1. テストの実装
   - SearchServiceのユニットテスト
   - レート制限のテスト
   - ElasticSearch統合テスト

2. パフォーマンスの最適化
   - インデックス設定の最適化
   - キャッシュ戦略の実装
   - クエリパフォーマンスの改善

3. ドキュメントの更新
   - API仕様書の更新
   - 検索機能のドキュメント作成
   - デプロイ手順の更新

#### 技術的な課題
1. ElasticSearch関連
   - インデックス管理の最適化
   - マッピング設定の最適化
   - レプリケーション戦略の検討

2. パフォーマンス
   - 検索クエリの最適化
   - レート制限の調整
   - キャッシュ戦略の実装

3. セキュリティ
   - ElasticSearch接続の暗号化
   - API認証の強化
   - レート制限の細分化

#### 使用技術の追加
- NEST (Elasticsearch.Net) 7.17.5
- Microsoft.AspNetCore.RateLimiting

### 2024-02-07
#### テストコードの実装と改善
1. SearchServiceTestsの実装
   - テストケースの作成と実行
   - NullReferenceExceptionの修正
   - モックの設定改善
   
2. 発生した問題と解決策
   - AggregateDictionaryのモック化の問題
     - 原因：パラメータレスコンストラクタの不在
     - 解決：nullを返すように修正
   - クエリ構築のNullReferenceException
     - 原因：SearchParametersの必須パラメータ不足
     - 解決：SearchFieldとMatchExactPhraseを追加
   - DeleteAsyncメソッドの引数型の不一致
     - 原因：DocumentPath<T>とIdの型の不一致
     - 解決：正しい引数型に修正

3. テストコードの改善点
   - Callbackを使用したリクエスト検証の追加
   - 例外メッセージの検証の追加
   - モックの設定の詳細化

#### 実装済み機能
- [x] SearchServiceの基本的なユニットテスト
- [x] 検索パラメータのバリデーション
- [x] エラーハンドリングのテスト
- [x] インデックス操作のテスト

#### 未解決の問題
1. テスト関連
   - [ ] 統合テストの実装
   - [ ] エッジケースのテスト追加
   - [ ] パフォーマンステストの実装
   - [ ] モック化できないクラスの対応

2. 検索機能
   - [ ] 複雑なクエリのテスト
   - [ ] ファセット検索の完全なテスト
   - [ ] ソート機能のテスト改善

3. エラーハンドリング
   - [ ] より詳細なエラーメッセージ
   - [ ] エラーケースの網羅
   - [ ] ログ出力の改善

#### 次のステップ
1. テストの拡充
   - 統合テストの実装
   - カバレッジの向上
   - テストデータの整備

2. 検索機能の改善
   - 複雑なクエリのサポート
   - パフォーマンスの最適化
   - エラーハンドリングの強化

3. ドキュメントの更新
   - テスト仕様書の作成
   - APIドキュメントの更新
   - トラブルシューティングガイドの作成

#### 技術的な課題
1. テスト関連
   - モック化の複雑さ
   - テストの保守性
   - テストデータの管理

2. 検索機能
   - クエリ最適化
   - インデックス管理
   - キャッシュ戦略

3. CI/CD
   - テスト自動化
   - デプロイパイプライン
   - 環境差異の管理

#### 参考資料・証跡
1. コードの変更
   - SearchServiceTests.cs: テストケースの実装
   - SearchService.cs: NullReferenceExceptionの修正
   - Program.cs: ElasticSearch設定の調整

2. テスト結果
   - NullReferenceException: 75行目で発生
   - AggregateDictionaryのモック化エラー
   - DeleteAsyncの引数型不一致

3. 使用技術
   - xUnit: テストフレームワーク
   - Moq: モッキングライブラリ
   - FluentAssertions: アサーションライブラリ
   - NEST: ElasticSearchクライアント 