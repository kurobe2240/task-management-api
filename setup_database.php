<?php
declare(strict_types=1);

// .envファイルから環境変数を読み込む
function loadEnv($path) {
    if (!file_exists($path)) {
        die(".envファイルが見つかりません。\n");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/.env');

$config = require __DIR__ . '/src/config/database.php';

try {
    // rootユーザーでMySQLに接続
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // データベースの作成
    $dbname = $config['database'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET {$config['charset']} COLLATE {$config['collation']}");
    echo "データベース '$dbname' を作成しました。\n";

    // 作成したデータベースを選択
    $pdo->exec("USE `$dbname`");

    // マイグレーションファイルを読み込んで実行
    $sql = file_get_contents(__DIR__ . '/database/migrations/001_create_initial_tables.sql');
    $pdo->exec($sql);
    echo "マイグレーションを実行しました。\n";

    echo "データベースのセットアップが完了しました。\n";

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("エラー: " . $e->getMessage() . "\n");
}
