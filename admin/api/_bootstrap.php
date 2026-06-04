<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=UTF-8');
// CORS: 管理画面からの直接アクセスのみ許可（ワイルドカード禁止）
$allowed_origins = ['https://coroproject.jp'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Headers: X-Api-Key, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

define('CORO_API_KEY', 'a473997a0ca9348cbcdf58aa2bea270f3ff27edc6eadbfca80bad2e1ec2ffd20');

$config  = require dirname(__DIR__) . '/config.php';
$dbPath  = $config['paths']['db_file'];
if (!file_exists($dbPath)) { api_error(500, 'DB not found'); }
require_once $dbPath;
if (!isset($pdo) || !($pdo instanceof PDO)) { api_error(500, 'DB error'); }

date_default_timezone_set('Asia/Tokyo');
mb_internal_encoding('UTF-8');

// API Key 認証
$key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals(CORO_API_KEY, $key)) {
    api_error(401, 'Invalid API key');
}

// メソッドオーバーライド（ApacheがPATCH/PUT/DELETEを拒否する場合の対策）
// POST + X-HTTP-Method-Override: PATCH で代替できる
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '';
    if (in_array(strtoupper($override), ['PUT', 'PATCH', 'DELETE'], true)) {
        $_SERVER['REQUEST_METHOD'] = strtoupper($override);
    }
}

// レスポンスヘルパー
function api_ok($data = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function api_error(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// リクエストボディ取得
function api_input(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

// URLからIDを取得（例: /api/talents/3 → 3, /api/clients/client-abc → "client-abc"）
// エンドポイント名自体は除外する
function api_path_id() {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $parts = array_values(array_filter(explode('/', $uri)));
    $last = end($parts);
    if ($last === false || $last === '') { return null; }
    $endpoints = ['talents','clients','deals','invoices','revenues','journal','payments','update','migrate'];
    if (in_array($last, $endpoints, true)) { return null; }
    return is_numeric($last) ? (int)$last : $last;
}
