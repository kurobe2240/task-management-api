<?php
declare(strict_types=1);

// 本番環境用のエラー設定
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', 'Off');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
}

// AutoloaderとBootstrap
require __DIR__ . '/../vendor/autoload.php';

// 環境変数の読み込み
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../src', '.env.production');
$dotenv->load();

// アプリケーションの実行
require __DIR__ . '/../public/index.php';
