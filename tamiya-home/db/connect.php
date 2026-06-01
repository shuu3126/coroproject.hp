<?php

// CORO_DB_PASS が設定されていれば本番、なければローカル(XAMPP)
$coroPass = getenv('CORO_DB_PASS');

$host   = 'localhost';
$dbname = $coroPass !== false ? 'coroproject_2' : 'tamiya_home';
$user   = $coroPass !== false ? 'db_coroproject' : 'root';
$pass   = $coroPass !== false ? $coroPass : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB接続エラー: ' . $e->getMessage());
}
