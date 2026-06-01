<?php

// ローカル: root/tamiya_home、本番: coroproject サーバーの設定を使用
$isLocal = (bool)preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $_SERVER['HTTP_HOST'] ?? '');

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
