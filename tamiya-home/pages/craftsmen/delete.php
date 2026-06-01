<?php
require_once __DIR__ . '/../../db/connect.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tamiya-home/pages/craftsmen/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM craftsmen WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: /tamiya-home/pages/craftsmen/index.php');
exit;
