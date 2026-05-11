<?php
// Moved to admin/mail/detail.php
require_once __DIR__ . '/_bootstrap.php';
$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . rtrim($baseUrl, '/') . '/mail/detail.php' . ($qs !== '' ? '?' . $qs : ''), true, 301);
exit;
