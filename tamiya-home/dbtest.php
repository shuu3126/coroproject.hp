<?php
// 確認後すぐ削除
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)
        || str_starts_with($_SERVER['HTTP_HOST'] ?? '', 'localhost:');

$host   = 'localhost';
$dbname = $isLocal ? 'tamiya_home'    : 'coroproject_2';
$user   = $isLocal ? 'root'           : 'db_coroproject';
$pass   = $isLocal ? ''               : (getenv('CORO_DB_PASS') ?: '');

echo "host=$host, dbname=$dbname, user=$user<br>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    echo "DB接続成功！";
} catch (PDOException $e) {
    echo "DB接続失敗: " . $e->getMessage();
}
