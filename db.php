<?php
// db.php（ミニムサーバー用）

$DB_HOST = 'localhost';
$DB_NAME = 'db_coroproject_1';
$DB_USER = 'db_coroproject';
$DB_PASS = '4SLawVX2';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}
