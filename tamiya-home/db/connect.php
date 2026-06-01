<?php

// ローカル(XAMPP): root / tamiya_home
// 本番(coroproject.jp): db_coroproject / coroproject_2
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)
        || str_starts_with($_SERVER['HTTP_HOST'] ?? '', 'localhost:');

$host   = 'localhost';
$dbname = $isLocal ? 'tamiya_home'    : 'coroproject_2';
$user   = $isLocal ? 'root'           : 'db_coroproject';
$pass   = $isLocal ? ''               : (getenv('CORO_DB_PASS') ?: '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB接続エラー: ' . $e->getMessage());
}
