<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Tokyo');
mb_internal_encoding('UTF-8');

$dbPath = dirname(__DIR__, 2) . '/db.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    exit('db.php が見つかりません。coroproject.jp/db.php を確認してください。');
}
require_once $dbPath;

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('db.php から PDO を取得できませんでした。');
}

session_name($config['app']['session_name'] ?? 'coro_admin_session');

$sessionLifetime = 60 * 60 * 24; // 24時間

ini_set('session.gc_maxlifetime', (string)$sessionLifetime);

$sessionSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'domain' => '',
    'secure' => $sessionSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| ログイン有効期限チェック
| ・ログインしてから24時間経過したらセッション破棄
|--------------------------------------------------------------------------
*/
if (!empty($_SESSION['admin_user'])) {
    $loginAt = isset($_SESSION['login_at']) ? (int)$_SESSION['login_at'] : 0;

    if ($loginAt <= 0 || (time() - $loginAt) > $sessionLifetime) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        // 失効後に再度セッション開始してメッセージを持たせる
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'ログインの有効期限が切れました。もう一度ログインしてください。',
        ];
    }
}

$baseUrl = rtrim($config['app']['base_url'] ?? '/admin', '/');
$publicRoot = dirname(__DIR__);

require_once __DIR__ . '/_functions.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_log.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/_pdf.php';
require_once __DIR__ . '/_accounting.php';