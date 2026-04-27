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

function admin_trim_url_path($path) {
    $path = '/' . trim((string)$path, '/');
    return $path === '/' ? '' : $path;
}

function admin_join_url_path($base, $path) {
    $base = admin_trim_url_path($base);
    $path = trim((string)$path, '/');
    if ($path === '') {
        return ltrim($base, '/');
    }
    return ltrim($base . '/' . $path, '/');
}

function admin_detect_base_url($configuredBaseUrl) {
    $configuredBaseUrl = trim((string)$configuredBaseUrl);
    if ($configuredBaseUrl !== '') {
        return admin_trim_url_path($configuredBaseUrl);
    }

    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $requestPath = (string)(parse_url($requestUri, PHP_URL_PATH) ?: '');
    if ($requestPath !== '') {
        $adminPos = strpos($requestPath, '/admin');
        if ($adminPos !== false) {
            $afterAdmin = substr($requestPath, $adminPos + 6, 1);
            if ($afterAdmin === '' || $afterAdmin === '/') {
                return admin_trim_url_path(substr($requestPath, 0, $adminPos + 6));
            }
        }
    }

    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptPath = (string)(parse_url($scriptName, PHP_URL_PATH) ?: '');
    if ($scriptPath === '' || $scriptPath === '.' || $scriptPath === '/') {
        return '/production/html/admin';
    }
    $scriptDir = admin_trim_url_path(dirname($scriptPath));

    if (basename($scriptDir) === 'accounting') {
        $scriptDir = admin_trim_url_path(dirname($scriptDir));
    }

    return $scriptDir !== '' ? $scriptDir : '/production/html/admin';
}

function admin_detect_public_url_root($baseUrl) {
    $baseUrl = admin_trim_url_path($baseUrl);
    foreach (['/html/admin', '/admin'] as $suffix) {
        if (substr($baseUrl, -strlen($suffix)) === $suffix) {
            return admin_trim_url_path(substr($baseUrl, 0, -strlen($suffix)));
        }
    }
    return admin_trim_url_path(dirname(dirname($baseUrl)));
}

$baseUrl = admin_detect_base_url($config['app']['base_url'] ?? '');
$publicUrlRoot = admin_detect_public_url_root($baseUrl);
$publicRoot = dirname(__DIR__);

if (empty($config['uploads']['news_public_prefix'])) {
    $config['uploads']['news_public_prefix'] = admin_join_url_path($publicUrlRoot, 'images/news');
}
if (empty($config['uploads']['talent_public_prefix'])) {
    $config['uploads']['talent_public_prefix'] = admin_join_url_path($publicUrlRoot, 'images/talents');
}
if (empty($config['uploads']['accounting_prefix'])) {
    $config['uploads']['accounting_prefix'] = admin_join_url_path($publicUrlRoot, 'uploads/accounting');
}

require_once __DIR__ . '/_functions.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_log.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/_pdf.php';
require_once __DIR__ . '/_accounting.php';
require_once dirname(__DIR__, 3) . '/includes/inquiries.php';
require_once dirname(__DIR__, 3) . '/includes/inquiry_mailer.php';
