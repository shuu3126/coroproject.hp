<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/logger.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tamiya-home/pages/assignments/index.php'); exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT c.name AS c, s.name AS s FROM assignments a JOIN craftsmen c ON a.craftsman_id=c.id JOIN sites s ON a.site_id=s.id WHERE a.id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $pdo->prepare('DELETE FROM assignments WHERE id = ?')->execute([$id]);
    if ($row) log_action($pdo, 'delete', 'アサイン', $row['c'], $row['s'] . ' を解除');
}

header('Location: /tamiya-home/pages/assignments/index.php');
exit;
