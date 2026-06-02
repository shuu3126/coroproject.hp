<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/logger.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tamiya-home/pages/sites/index.php');
    exit;
}

$comment_id = (int)($_POST['comment_id'] ?? 0);
$site_id = (int)($_POST['site_id'] ?? 0);
$redirect = $site_id > 0
    ? '/tamiya-home/pages/sites/detail.php?id=' . $site_id . '#comments'
    : '/tamiya-home/pages/sites/index.php';

if ($comment_id > 0 && $site_id > 0) {
    $stmt = $pdo->prepare("
        SELECT sc.id, sc.site_id, sc.body, s.name AS site_name
        FROM site_comments sc
        JOIN sites s ON sc.site_id = s.id
        WHERE sc.id = ? AND sc.site_id = ?
    ");
    $stmt->execute([$comment_id, $site_id]);
    $comment = $stmt->fetch();

    if ($comment) {
        $stmt = $pdo->prepare('DELETE FROM site_comments WHERE id = ? AND site_id = ?');
        $stmt->execute([$comment_id, $site_id]);

        log_action($pdo, 'delete', '現場コメント', $comment['site_name'], $comment['body']);
    }
}

header('Location: ' . $redirect);
exit;
