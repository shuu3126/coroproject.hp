<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/logger.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tamiya-home/pages/sites/index.php'); exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT name FROM sites WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $pdo->prepare('DELETE FROM sites WHERE id = ?')->execute([$id]);
    if ($row) log_action($pdo, 'delete', '現場', $row['name']);
}

header('Location: /tamiya-home/pages/sites/index.php');
exit;
