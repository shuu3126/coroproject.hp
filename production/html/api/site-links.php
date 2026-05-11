<?php
require_once __DIR__ . '/../../../includes/public-settings.php';

header('Content-Type: application/json; charset=UTF-8');

$settings = coro_public_settings();
echo json_encode([
    'ok' => true,
    'mail' => coro_public_mail_address($settings),
    'social_links' => coro_public_social_links($settings),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
