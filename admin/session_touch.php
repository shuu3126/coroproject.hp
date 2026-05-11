<?php
require_once __DIR__ . '/_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!current_admin_user()) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

echo json_encode([
    'ok' => true,
    'expires_in' => (int)$adminSessionIdleTimeout,
]);
