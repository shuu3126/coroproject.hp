<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/logger.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tamiya-home/pages/sites/index.php');
    exit;
}

$site_id = (int)($_POST['site_id'] ?? 0);
$redirect = $site_id > 0
    ? '/tamiya-home/pages/sites/detail.php?id=' . $site_id . '#comments'
    : '/tamiya-home/pages/sites/index.php';

if ($site_id <= 0) {
    header('Location: ' . $redirect);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name FROM sites WHERE id = ?');
$stmt->execute([$site_id]);
$site = $stmt->fetch();
if (!$site) {
    header('Location: /tamiya-home/pages/sites/index.php');
    exit;
}

$body = trim($_POST['body'] ?? '');
if ($body === '') {
    header('Location: ' . $redirect);
    exit;
}

if (function_exists('mb_substr')) {
    $body = mb_substr($body, 0, 1000, 'UTF-8');
} else {
    $body = substr($body, 0, 1000);
}

$user = currentUser();
$user_name = $user['name'] ?: '不明';

$stmt = $pdo->prepare("
    INSERT INTO site_comments (site_id, user_id, user_name, body)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([
    $site_id,
    $user['id'] ?: null,
    $user_name,
    $body,
]);

log_action($pdo, 'create', '現場コメント', $site['name'], $body);

header('Location: ' . $redirect);
exit;
