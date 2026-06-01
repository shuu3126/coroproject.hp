<?php
// 確認後すぐ削除
$isProd = strpos($_SERVER['HTTP_HOST'] ?? '', 'coroproject.jp') !== false;

$host   = 'localhost';
$dbname = $isProd ? 'db_coroproject_2'  : 'tamiya_home';
$user   = $isProd ? 'db_coroproject' : 'root';
$pass   = $isProd ? 'FwMMCTUO'       : '';

echo "isProd=" . ($isProd ? 'true' : 'false') . ", user=$user, dbname=$dbname<br>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    echo "DB接続成功！";
} catch (PDOException $e) {
    echo "DB接続失敗: " . $e->getMessage();
}
