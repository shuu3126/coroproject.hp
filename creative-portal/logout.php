<?php
require_once __DIR__ . '/_bootstrap.php';

$creator = cp_current_creator();
if ($creator) {
    cp_write_activity($pdo, $creator['creator_id'], (int)$creator['id'], 'logout', 'Creativeポータルからログアウト');
}
cp_logout();
cp_redirect($creativePortalBase . '/login.php');
