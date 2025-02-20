# データベースマイグレーションの実行
Write-Host "データベースマイグレーションを実行中..."
dotnet ef database update

# 発行
Write-Host "アプリケーションを発行中..."
dotnet publish -c Release -o ./publish

# 環境変数の設定
Write-Host "環境変数を設定中..."
$env:ASPNETCORE_ENVIRONMENT = "Production"

Write-Host "デプロイが完了しました。" 