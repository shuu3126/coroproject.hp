<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.php';
$dbPath = dirname(__DIR__, 2) . '/db.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    exit('db.php が見つかりません。html/db.php を配置してください。');
}
require_once $dbPath;

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('db.php から PDO を取得できませんでした。');
}

session_name($config['app']['session_name'] ?? 'coro_admin_session');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Tokyo');
mb_internal_encoding('UTF-8');

$baseUrl = rtrim($config['app']['base_url'] ?? '/admin', '/');
$publicRoot = dirname(__DIR__);

require_once __DIR__ . '/_functions.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_log.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/_pdf.php';
require_once __DIR__ . '/_accounting.php';
