# デプロイメントガイド

## フロントエンド（React + Vite）

### 準備
1. Vercelアカウントを作成し、GitHubリポジトリと連携
2. 以下の環境変数を設定
   ```
   VITE_API_URL=https://your-api-domain.com
   VITE_APP_ENV=production
   VITE_APP_NAME=TaskManagement
   ```

### デプロイ手順
1. GitHubにプッシュ
2. Vercelダッシュボードで新規プロジェクトを作成
3. フレームワークプリセットとして「Vite」を選択
4. ビルドコマンドとして `npm run build` を確認
5. 出力ディレクトリとして `dist` を確認
6. デプロイを実行

## バックエンド（PHP API）

### 準備
1. PlanetScaleまたは他のMySQLホスティングサービスでデータベースを作成
2. Vercelで以下の環境変数を設定
   ```
   APP_ENV=production
   APP_DEBUG=false
   DB_HOST=your-db-host
   DB_PORT=3306
   DB_DATABASE=your-db-name
   DB_USERNAME=your-db-user
   DB_PASSWORD=your-db-password
   APP_SECRET=your-production-secret
   CORS_ALLOW_ORIGIN=https://your-frontend-domain.vercel.app
   ```

### デプロイ手順
1. GitHubにプッシュ
2. Vercelダッシュボードで新規プロジェクトを作成
3. ルートディレクトリを `api` に設定
4. ビルド設定は `vercel.json` に従う
5. デプロイを実行

## 本番環境の確認事項

### セキュリティ
- すべての機密情報は環境変数として設定
- デバッグモードが無効化されていることを確認
- 適切なCORS設定の確認
- セキュアなデータベース接続の確認

### パフォーマンス
- アセットの最適化
- キャッシュヘッダーの設定
- データベースインデックスの確認

### モニタリング
- エラーログの確認方法
- パフォーマンスモニタリングの設定
- ヘルスチェックエンドポイントの確認

## トラブルシューティング

### よくある問題
1. CORS エラー
   - CORS_ALLOW_ORIGINの設定を確認
   - フロントエンドドメインが正しく設定されているか確認

2. データベース接続エラー
   - 環境変数の設定を確認
   - ファイアウォール設定の確認
   - 接続文字列の確認

3. 500エラー
   - ログを確認
   - デバッグモードを一時的に有効化して詳細を確認

### デプロイ後の確認項目
- [ ] フロントエンドからバックエンドへの疎通確認
- [ ] データベース接続の確認
- [ ] ユーザー認証の動作確認
- [ ] ファイルアップロードの確認（該当する場合）
- [ ] すべてのAPIエンドポイントの動作確認
