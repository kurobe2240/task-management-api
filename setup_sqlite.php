<?php
try {
    // データベースディレクトリの作成
    if (!file_exists('database')) {
        mkdir('database', 0777, true);
    }

    // SQLiteデータベースファイルの作成
    $dbFile = 'database/database.sqlite';
    if (!file_exists($dbFile)) {
        touch($dbFile);
        chmod($dbFile, 0777);
    }

    // データベース接続
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // マイグレーションファイルの読み込みと実行
    $sql = file_get_contents('database/migrations/sqlite_initial_tables.sql');
    $pdo->exec($sql);
    echo "データベースのセットアップが完了しました。\n";

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("エラー: " . $e->getMessage() . "\n");
}
