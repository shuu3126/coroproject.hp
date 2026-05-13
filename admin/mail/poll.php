<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin_login();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$settings = load_app_settings($pdo, $config);
$receiveReady = admin_mail_receive_ready($settings);

$inserted = 0;
if ($receiveReady) {
    try {
        $u = current_admin_user();
        $result = admin_mail_sync_receive($pdo, $settings, (int)$u['id']);
        $inserted = (int)($result['inserted'] ?? 0);
    } catch (Exception $e) {
        // silent
    }
}

echo json_encode([
    'new'    => $inserted,
    'unread' => admin_mail_unread_count($pdo),
]);
