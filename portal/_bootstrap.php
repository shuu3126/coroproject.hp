<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

date_default_timezone_set('Asia/Tokyo');
mb_internal_encoding('UTF-8');

$dbPath = dirname(__DIR__) . '/production/db.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    exit('DB設定ファイルが見つかりません。');
}
require_once $dbPath;

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('DB接続に失敗しました。');
}

define('PORTAL_ROOT',       __DIR__);
define('PORTAL_UPLOAD_DIR', __DIR__ . '/uploads');
define('PORTAL_SESSION_TIMEOUT', 60 * 60 * 2);

ini_set('session.gc_maxlifetime', PORTAL_SESSION_TIMEOUT);

$sessionSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('coro_portal_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $sessionSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['portal_talent'])) {
    $now          = time();
    $lastActivity = (int)($_SESSION['portal_last_activity'] ?? 0);
    if ($lastActivity > 0 && ($now - $lastActivity) > PORTAL_SESSION_TIMEOUT) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['portal_flash'] = ['type' => 'error', 'message' => '無操作が続いたためログアウトしました。再ログインしてください。'];
    } else {
        $_SESSION['portal_last_activity'] = $now;
    }
}

require_once __DIR__ . '/_functions.php';
require_once __DIR__ . '/_auth.php';

function portal_base_url() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos    = strpos($script, '/portal');
    if ($pos === false) return '/portal';
    return rtrim(substr($script, 0, $pos + strlen('/portal')), '/');
}

$portalBase = portal_base_url();
