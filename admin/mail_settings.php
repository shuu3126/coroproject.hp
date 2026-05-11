<?php
// Moved to admin/mail/settings.php
require_once __DIR__ . '/_bootstrap.php';
$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . rtrim($baseUrl, '/') . '/mail/settings.php' . ($qs !== '' ? '?' . $qs : ''), true, 301);
exit;
