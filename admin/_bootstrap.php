<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$config = require __DIR__ . '/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Tokyo');
mb_internal_encoding('UTF-8');

$dbPath = $config['paths']['db_file'] ?? (dirname(__DIR__) . '/production/db.php');
if (!file_exists($dbPath)) {
    http_response_code(500);
    exit('db.php が見つかりません。production/db.php を確認してください。');
}
require_once $dbPath;

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('db.php から PDO を取得できませんでした。');
}

function admin_base_url($configuredBaseUrl = null) {
    $configuredBaseUrl = trim((string)$configuredBaseUrl);
    if ($configuredBaseUrl !== '') {
        return rtrim($configuredBaseUrl, '/');
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/admin/index.php');
    $adminPos = strpos($scriptName, '/admin');
    if ($adminPos === false) {
        return '/admin';
    }

    return rtrim(substr($scriptName, 0, $adminPos + strlen('/admin')), '/');
}

function admin_project_base_url($baseUrl) {
    $adminPos = strrpos($baseUrl, '/admin');
    if ($adminPos === false) {
        return '';
    }

    return rtrim(substr($baseUrl, 0, $adminPos), '/');
}

function admin_public_url($path) {
    global $projectBaseUrl;

    $path = trim(str_replace('\\', '/', (string)$path));
    if ($path === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    $path = ltrim($path, '/');
    if (strpos($path, 'coroproject_jp/images/') === 0) {
        $path = 'production/' . substr($path, strlen('coroproject_jp/'));
    }

    return rtrim($projectBaseUrl, '/') . '/' . $path;
}

$baseUrl = admin_base_url($config['app']['base_url'] ?? null);
$projectBaseUrl = admin_project_base_url($baseUrl);
$publicRoot = dirname(__DIR__);

session_name($config['app']['session_name'] ?? 'coro_admin_session');

$adminSessionIdleTimeout = (int)($config['app']['session_idle_timeout'] ?? (60 * 60));
if ($adminSessionIdleTimeout <= 0) {
    $adminSessionIdleTimeout = 60 * 60;
}
ini_set('session.gc_maxlifetime', (string)$adminSessionIdleTimeout);

$sessionSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_set_cookie_params([
    'lifetime' => (int)($config['app']['session_cookie_lifetime'] ?? 0),
    'path' => '/',
    'domain' => '',
    'secure' => $sessionSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function admin_clear_session($flashType = null, $flashMessage = null) {
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    if ($flashMessage !== null && $flashMessage !== '') {
        $_SESSION['flash'] = [
            'type' => $flashType ?: 'info',
            'message' => $flashMessage,
        ];
    }
}

if (!empty($_SESSION['admin_user'])) {
    $now = time();
    $lastActivityAt = isset($_SESSION['admin_last_activity_at'])
        ? (int)$_SESSION['admin_last_activity_at']
        : (int)($_SESSION['login_at'] ?? 0);

    if ($lastActivityAt <= 0 || ($now - $lastActivityAt) > $adminSessionIdleTimeout) {
        admin_clear_session('error', '無操作が1時間続いたため、自動的にログアウトしました。もう一度ログインしてください。');
    } else {
        $_SESSION['admin_last_activity_at'] = $now;
    }
}

require_once __DIR__ . '/_functions.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_log.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/_pdf.php';
require_once __DIR__ . '/_accounting.php';
require_once __DIR__ . '/_mail.php';
require_once __DIR__ . '/_data_transfer.php';
require_once __DIR__ . '/_creative_portal.php';

$_adminScriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$_adminCsrfExempt = substr($_adminScriptName, -strlen('/admin/system/session_touch.php')) === '/admin/system/session_touch.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && current_admin_user() && !$_adminCsrfExempt) {
    $token = $_POST['_csrf'] ?? '';
    if (!admin_verify_csrf($token)) {
        set_flash('error', '不正なリクエストです。ページを再読み込みして再試行してください。');
        $fallback = $baseUrl . '/index.php';
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $refererHost = $referer !== '' ? parse_url($referer, PHP_URL_HOST) : '';
        if ($refererHost !== '' && hash_equals((string)($_SERVER['HTTP_HOST'] ?? ''), (string)$refererHost)) {
            $fallback = $referer;
        }
        redirect_to($fallback);
    }
}
