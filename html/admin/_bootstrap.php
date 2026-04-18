<?php
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('config.php がありません。config.sample.php をコピーして設定してください。');
}
$config = require $configPath;

session_name($config['app']['session_name'] ?? 'coro_admin_session');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Tokyo');

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $config['db']['host'],
    $config['db']['dbname'],
    $config['db']['charset'] ?? 'utf8mb4'
);

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    die('DB接続に失敗しました: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

require_once __DIR__ . '/_functions.php';
require_once __DIR__ . '/_log.php';
require_once __DIR__ . '/_upload.php';

$baseUrl = rtrim($config['app']['base_url'] ?? '/admin', '/');
$publicRoot = dirname(__DIR__);
