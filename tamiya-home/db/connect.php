<?php

// ローカル(XAMPP)と本番で自動切り替え
$host   = getenv('DB_HOST')   ?: 'localhost';
$dbname = getenv('DB_NAME')   ?: 'tamiya_home';
$user   = getenv('DB_USER')   ?: 'root';
$pass   = getenv('DB_PASS')   ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB接続エラー: ' . $e->getMessage());
}
