<?php
// Moved to admin/mail/index.php
require_once __DIR__ . '/_bootstrap.php';
$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . rtrim($baseUrl, '/') . '/mail/index.php' . ($qs !== '' ? '?' . $qs : ''), true, 301);
exit;
