<?php

// ローカル(XAMPP): root / tamiya_home
// 本番(coroproject.jp): db_coroproject / coroproject_2
$isProd  = strpos($_SERVER['HTTP_HOST'] ?? '', 'coroproject.jp') !== false;

$host   = 'localhost';
$dbname = $isProd ? 'coroproject_2'  : 'tamiya_home';
$user   = $isProd ? 'db_coroproject' : 'root';
$pass   = $isProd ? 'FaMkCTUO'       : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB接続エラー: ' . $e->getMessage());
}
