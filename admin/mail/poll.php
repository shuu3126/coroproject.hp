<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$settings = load_app_settings($pdo, $config);
$popReady = admin_mail_setting($settings, 'mail_pop_host') !== ''
    && admin_mail_setting($settings, 'mail_pop_user', admin_mail_setting($settings, 'smtp_user')) !== ''
    && admin_mail_setting($settings, 'mail_pop_pass', admin_mail_setting($settings, 'smtp_pass')) !== '';

$inserted = 0;
if ($popReady) {
    try {
        $u = current_admin_user();
        $result = admin_mail_sync_pop3($pdo, $settings, (int)$u['id']);
        $inserted = (int)($result['inserted'] ?? 0);
    } catch (Exception $e) {
        // silent
    }
}

echo json_encode([
    'new'    => $inserted,
    'unread' => admin_mail_unread_count($pdo),
]);
