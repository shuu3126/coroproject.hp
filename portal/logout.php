<?php
require_once __DIR__ . '/_bootstrap.php';
$talent = current_portal_talent();
if ($talent) {
    portal_write_activity($pdo, $talent['talent_id'], (int)$talent['id'], 'logout', 'ポータルからログアウト');
}
portal_logout();
portal_flash_set('info', 'ログアウトしました。');
portal_redirect($portalBase . '/login.php');
