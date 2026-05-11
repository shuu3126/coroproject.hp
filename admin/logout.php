<?php
require_once __DIR__ . '/_bootstrap.php';
$timeoutLogout = (($_GET['reason'] ?? '') === 'timeout');
$user = current_admin_user();
if ($user) {
    write_admin_log($pdo, (int)$user['id'], 'logout', 'admin_user', (int)$user['id'], 'ログアウトしました');
}

if ($user || $timeoutLogout) {
    admin_clear_session(
        $timeoutLogout ? 'error' : null,
        $timeoutLogout ? '無操作が1時間続いたため、自動的にログアウトしました。もう一度ログインしてください。' : null
    );
}
header('Location: ' . $baseUrl . '/login.php');
exit;
