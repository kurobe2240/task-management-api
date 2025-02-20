<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// 環境変数の読み込み
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// エラー表示設定
if ($_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// タイムゾーンの設定
date_default_timezone_set('Asia/Tokyo');

// コンテナビルダーの作成
$containerBuilder = new ContainerBuilder();

// 依存関係の定義を読み込み
$dependencies = require __DIR__ . '/../src/config/container.php';
$dependencies($containerBuilder);

// コンテナの構築
$container = $containerBuilder->build();

// アプリケーションの作成
AppFactory::setContainer($container);
$app = AppFactory::create();

// ベースパスの設定（必要な場合）
// $app->setBasePath('/api');

// ルーティングの設定
$routes = require __DIR__ . '/../src/config/routes.php';
$routes($app);

// アプリケーションの実行
$app->run();
