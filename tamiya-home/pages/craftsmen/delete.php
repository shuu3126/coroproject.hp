<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/logger.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tamiya-home/pages/craftsmen/index.php'); exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT name FROM craftsmen WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $pdo->prepare('DELETE FROM craftsmen WHERE id = ?')->execute([$id]);
    if ($row) log_action($pdo, 'delete', '職人', $row['name']);
}

header('Location: /tamiya-home/pages/craftsmen/index.php');
exit;
